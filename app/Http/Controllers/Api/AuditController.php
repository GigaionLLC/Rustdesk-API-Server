<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditConn;
use App\Models\AuditFile;
use App\Models\Device;
use App\Services\AlarmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Audit ingestion endpoints (docs/modernization/02-client-api-contract.md §8,
 * docs/modernization/13-deepscan-connection.md).
 *
 * Unauthenticated: hbbs and clients POST connection, file-transfer and security-alarm events
 * here. We persist them as AuditConn / AuditFile / Alarm rows.
 */
class AuditController extends Controller
{
    /**
     * The RustDesk client's connection alarm types (AlarmAuditType in the client).
     *
     * @var array<int, string>
     */
    public const ALARM_TYPES = [
        0 => 'Connection from a non-whitelisted IP',
        1 => 'Excessive login attempts (>30)',
        2 => 'Rapid login attempts (6/min)',
        6 => 'IPv6 prefix abuse',
        7 => 'Terminal OS-login backoff',
        8 => 'Terminal session concurrency limit',
    ];

    /**
     * POST /api/audit/conn
     * Two shapes:
     *  - host connection event:  { id, action:"new"|"close", conn_id, peer:[id,name]?, ip, session_id, type, uuid }
     *  - operator session note:  { id, session_id, note }   (no `action`)
     */
    public function conn(Request $request, AlarmService $alarms): JsonResponse
    {
        // Operator session note (posted by the controlling side) — attach to the open session.
        $note = $request->input('note');
        $sessionId = (string) $request->input('session_id', '');
        if ($note !== null && ! $request->has('action')) {
            if ($sessionId !== '') {
                AuditConn::where('session_id', $sessionId)
                    ->where('action', AuditConn::ACTION_NEW)
                    ->latest('id')
                    ->first()
                    ?->update(['note' => (string) $note]);
            }

            return response()->json([]);
        }

        $peer = $request->input('peer', []);
        $peer = is_array($peer) ? $peer : [];

        $action = (string) $request->input('action', AuditConn::ACTION_NEW);
        $peerId = (string) $request->input('id', '');
        $ip = (string) $request->input('ip', $request->ip());

        AuditConn::create([
            'action' => $action,
            'conn_id' => (int) $request->input('conn_id', 0),
            'peer_id' => $peerId,
            'from_peer' => (string) ($peer[0] ?? ''),
            'from_name' => (string) ($peer[1] ?? ''),
            'ip' => $ip,
            'session_id' => $sessionId,
            'type' => (int) $request->input('type', 0),
            'uuid' => (string) $request->input('uuid', ''),
            'closed_at' => $action === AuditConn::ACTION_CLOSE ? now() : null,
        ]);

        // Raise an alarm for newly established connections. Wrapped so audit never fails.
        if ($action === AuditConn::ACTION_NEW && $peerId !== '') {
            try {
                $device = Device::where('rustdesk_id', $peerId)->first();
                $fromName = (string) ($peer[1] ?? $peer[0] ?? '');
                $alarms->raise(
                    $device,
                    $peerId,
                    'new_connection',
                    'New connection to '.$peerId.($fromName !== '' ? ' from '.$fromName : '').' ('.$ip.')',
                    $ip
                );
            } catch (Throwable $e) {
                Log::error('AlarmService raise failed in audit conn', [
                    'peer_id' => $peerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([]);
    }

    /**
     * POST /api/audit/alarm
     * Body: { id, uuid, typ:<int>, info:<json string|object> } — connection security alarms.
     */
    public function alarm(Request $request, AlarmService $alarms): JsonResponse
    {
        $peerId = (string) $request->input('id', '');
        $typ = (int) $request->input('typ', -1);

        $info = $request->input('info', '');
        if (is_array($info)) {
            $info = json_encode($info);
        }
        $info = (string) $info;

        $label = self::ALARM_TYPES[$typ] ?? ('Security alarm (type '.$typ.')');
        $message = $info !== '' ? $label.': '.$info : $label;
        $ip = (string) $request->input('ip', $request->ip());

        try {
            $device = $peerId !== '' ? Device::where('rustdesk_id', $peerId)->first() : null;
            $alarms->raise($device, $peerId, $label, $message, $ip);
        } catch (Throwable $e) {
            Log::error('AlarmService raise failed in audit alarm', [
                'peer_id' => $peerId,
                'typ' => $typ,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([]);
    }

    /**
     * POST /api/audit/file
     * Body: { id, peer_id, info, is_file, path, type, ip, uuid }.
     */
    public function file(Request $request): JsonResponse
    {
        AuditFile::create([
            'peer_id' => (string) ($request->input('peer_id') ?? $request->input('id') ?? ''),
            'from_peer' => (string) $request->input('id', ''),
            'from_name' => (string) $request->input('from_name', ''),
            'info' => (string) $request->input('info', ''),
            'is_file' => $request->boolean('is_file', true),
            'path' => (string) $request->input('path', ''),
            'type' => (int) $request->input('type', 0),
            'ip' => (string) $request->input('ip', $request->ip()),
            'num' => (int) $request->input('num', 0),
            'uuid' => (string) $request->input('uuid', ''),
        ]);

        return response()->json([]);
    }
}
