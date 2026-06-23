<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Device deployment / CLI enrollment (docs/modernization/02-client-api-contract.md §7).
 *
 * Authenticated by a deployment token (Authorization: Bearer <deploy token>), distinct from
 * the account bearer tokens used elsewhere. Responds with a JSON object whose `result` the
 * client matches: OK | NOT_ENABLED | INVALID_INPUT | ID_TAKEN.
 */
class DevicesController extends Controller
{
    public function __construct(private readonly DeploymentService $deployment) {}

    /**
     * POST /api/devices/deploy — { id, uuid, pk } + bearer DeployToken.
     *
     * The client JSON-parses the body and reads a string `result` field
     * (`ui_interface.rs` deploy(): `serde_json::from_str(&text)["result"].as_str()`).
     * A bare text body would parse to null and surface as a spurious error, so this must
     * be a JSON object — see docs/modernization/16-response-contract.md §4.
     */
    public function deploy(Request $request): JsonResponse
    {
        $token = $this->deployment->resolveToken($request->bearerToken());

        $result = $this->deployment->deploy(
            $token,
            (string) $request->input('id', ''),
            (string) $request->input('uuid', ''),
            (string) $request->input('pk', ''),
        );

        return response()->json(['result' => $result]);
    }
}
