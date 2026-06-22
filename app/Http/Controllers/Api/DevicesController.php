<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeploymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Device deployment / CLI enrollment (docs/modernization/02-client-api-contract.md §7).
 *
 * Authenticated by a deployment token (Authorization: Bearer <deploy token>), distinct from
 * the account bearer tokens used elsewhere. Responds with a plain-text result string the
 * client matches: OK | NOT_ENABLED | INVALID_INPUT | ID_TAKEN.
 */
class DevicesController extends Controller
{
    public function __construct(private readonly DeploymentService $deployment) {}

    /**
     * POST /api/devices/deploy — { id, uuid, pk } + bearer DeployToken.
     */
    public function deploy(Request $request): Response
    {
        $token = $this->deployment->resolveToken($request->bearerToken());

        $result = $this->deployment->deploy(
            $token,
            (string) $request->input('id', ''),
            (string) $request->input('uuid', ''),
            (string) $request->input('pk', ''),
        );

        return response($result)->header('Content-Type', 'text/plain');
    }
}
