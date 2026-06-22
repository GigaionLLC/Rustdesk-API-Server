<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecordingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Session-recording chunked upload (docs/modernization/02-client-api-contract.md §5).
 *
 * Driven by the `type` query param; the request body is raw bytes for the part/tail phases.
 * Responds with {} on success or {"error":"<msg>"} on failure (any error aborts the upload).
 */
class RecordController extends Controller
{
    public function __construct(private readonly RecordingService $recordings) {}

    /**
     * POST /api/record?type=new|part|tail|remove&file=<name>&offset=<n>&length=<m>
     */
    public function store(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', '');
        $file = (string) $request->query('file', '');

        if ($file === '') {
            return response()->json(['error' => 'Missing file name']);
        }

        $offset = (int) $request->query('offset', 0);
        $length = (int) $request->query('length', 0);

        $result = match ($type) {
            'new' => $this->recordings->start(
                $file,
                (string) $request->query('id', ''),
                (string) $request->query('from', '') ?: null,
                $request->query('conn_id') !== null ? (int) $request->query('conn_id') : null,
            ),
            'part' => $this->recordings->part($file, $offset, $length, $request->getContent()),
            'tail' => $this->recordings->tail($file, $offset, $length, $request->getContent()),
            'remove' => $this->recordings->remove($file),
            default => ['error' => 'Unknown record type'],
        };

        return response()->json($result ?: (object) []);
    }
}
