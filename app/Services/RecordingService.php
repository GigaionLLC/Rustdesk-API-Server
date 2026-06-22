<?php

namespace App\Services;

use App\Models\Recording;

/**
 * Session-recording chunked upload handling
 * (docs/modernization/02-client-api-contract.md §5).
 *
 * Files are stored under storage/app/recordings; each upload is tracked by a Recording row.
 * The upload is driven by query params (type=new|part|tail|remove) with raw bytes as body.
 */
class RecordingService
{
    /** Maximum header length accepted in the `tail` phase. */
    private const MAX_HEADER = 1024;

    /**
     * Absolute directory recordings are written to.
     */
    public function storageDir(): string
    {
        return storage_path('app/recordings');
    }

    /**
     * Sanitise the client-supplied file name to a safe basename.
     */
    public function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        // Drop anything outside a conservative allow-list.
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? '';

        return $name;
    }

    public function pathFor(string $safeName): string
    {
        return $this->storageDir().DIRECTORY_SEPARATOR.$safeName;
    }

    private function ensureDir(): void
    {
        $dir = $this->storageDir();
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    /**
     * Phase "new": begin an upload. Truncates/creates the file and tracks a Recording row.
     *
     * @return array<string, mixed> response payload ({} on success, {error} on failure)
     */
    public function start(string $name, ?string $peerId, ?string $fromPeer, ?int $connId): array
    {
        $safe = $this->safeName($name);
        if ($safe === '') {
            return ['error' => 'Invalid file name'];
        }

        $this->ensureDir();
        $path = $this->pathFor($safe);

        if (file_put_contents($path, '') === false) {
            return ['error' => 'Could not create recording file'];
        }

        Recording::updateOrCreate(
            ['filename' => $safe],
            [
                'peer_id' => $peerId ?: '',
                'from_peer' => $fromPeer,
                'conn_id' => $connId,
                'path' => $path,
                'size' => 0,
                'status' => 'recording',
                'started_at' => now(),
                'finished_at' => null,
            ]
        );

        return [];
    }

    /**
     * Phase "part": write a body chunk at the given offset.
     *
     * @return array<string, mixed>
     */
    public function part(string $name, int $offset, int $length, string $body): array
    {
        $safe = $this->safeName($name);
        $path = $this->pathFor($safe);

        if ($safe === '' || ! is_file($path)) {
            return ['error' => 'Recording not started'];
        }

        if ($offset < 0 || $length < 0 || strlen($body) < $length) {
            return ['error' => 'Invalid chunk'];
        }

        $handle = @fopen($path, 'cb');
        if ($handle === false) {
            return ['error' => 'Could not open recording file'];
        }

        try {
            if (fseek($handle, $offset) !== 0) {
                return ['error' => 'Could not seek recording file'];
            }
            $chunk = $length > 0 ? substr($body, 0, $length) : $body;
            if (fwrite($handle, $chunk) === false) {
                return ['error' => 'Could not write recording chunk'];
            }
        } finally {
            fclose($handle);
        }

        $this->touchSize($safe, $path);

        return [];
    }

    /**
     * Phase "tail": write the (≤1024 byte) header to the head of the file and finalise.
     *
     * @return array<string, mixed>
     */
    public function tail(string $name, int $offset, int $length, string $body): array
    {
        $safe = $this->safeName($name);
        $path = $this->pathFor($safe);

        if ($safe === '' || ! is_file($path)) {
            return ['error' => 'Recording not started'];
        }

        if ($offset < 0 || $length < 0 || $length > self::MAX_HEADER || strlen($body) < $length) {
            return ['error' => 'Invalid header chunk'];
        }

        $handle = @fopen($path, 'cb');
        if ($handle === false) {
            return ['error' => 'Could not open recording file'];
        }

        try {
            if (fseek($handle, $offset) !== 0) {
                return ['error' => 'Could not seek recording file'];
            }
            $chunk = $length > 0 ? substr($body, 0, $length) : $body;
            if ($chunk !== '' && fwrite($handle, $chunk) === false) {
                return ['error' => 'Could not write recording header'];
            }
        } finally {
            fclose($handle);
        }

        $recording = Recording::where('filename', $safe)->first();
        if ($recording) {
            $recording->forceFill([
                'size' => is_file($path) ? (int) filesize($path) : $recording->size,
                'status' => 'finished',
                'finished_at' => now(),
            ])->save();
        }

        return [];
    }

    /**
     * Phase "remove": abort an upload, deleting the partial file and row.
     *
     * @return array<string, mixed>
     */
    public function remove(string $name): array
    {
        $safe = $this->safeName($name);
        if ($safe === '') {
            return ['error' => 'Invalid file name'];
        }

        $path = $this->pathFor($safe);
        if (is_file($path)) {
            @unlink($path);
        }

        Recording::where('filename', $safe)->delete();

        return [];
    }

    private function touchSize(string $safe, string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        Recording::where('filename', $safe)
            ->update(['size' => (int) filesize($path)]);
    }
}
