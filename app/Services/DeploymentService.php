<?php

namespace App\Services;

use App\Models\DeployToken;
use App\Models\Device;

/**
 * Device deployment / CLI assignment (docs/modernization/02-client-api-contract.md §7).
 *
 * Backs `rustdesk.exe --deploy` / `--assign`: a device enrolls itself with a deployment
 * token, returning one of OK | NOT_ENABLED | INVALID_INPUT | ID_TAKEN.
 */
class DeploymentService
{
    public const RESULT_OK = 'OK';

    public const RESULT_NOT_ENABLED = 'NOT_ENABLED';

    public const RESULT_INVALID_INPUT = 'INVALID_INPUT';

    public const RESULT_ID_TAKEN = 'ID_TAKEN';

    /**
     * Resolve a deploy token string to a non-expired DeployToken, or null.
     */
    public function resolveToken(?string $token): ?DeployToken
    {
        if ($token === null || trim($token) === '') {
            return null;
        }

        $deployToken = DeployToken::where('token', trim($token))->first();

        if (! $deployToken) {
            return null;
        }

        if ($deployToken->expires_at !== null && $deployToken->expires_at->isPast()) {
            return null;
        }

        return $deployToken;
    }

    /**
     * Enroll a device. Returns one of the RESULT_* constants.
     */
    public function deploy(?DeployToken $token, string $id, string $uuid, string $pk): string
    {
        // Feature gate: deployment must be enabled and a valid token presented.
        if (! config('rustdesk.devices.require_deployment') || ! $token) {
            return self::RESULT_NOT_ENABLED;
        }

        $id = trim($id);
        $uuid = trim($uuid);

        if ($id === '' || $uuid === '') {
            return self::RESULT_INVALID_INPUT;
        }

        $existing = Device::where('rustdesk_id', $id)->first();

        // Reject if the id is already owned by a different device (different uuid).
        if ($existing && $existing->uuid !== '' && $uuid !== '' && $existing->uuid !== $uuid) {
            return self::RESULT_ID_TAKEN;
        }

        $device = $existing ?: new Device(['rustdesk_id' => $id]);

        $device->fill([
            'uuid' => $uuid,
            'user_id' => $token->user_id,
            'approved' => true,
        ])->save();

        $token->forceFill(['last_used_at' => now()])->save();

        return self::RESULT_OK;
    }
}
