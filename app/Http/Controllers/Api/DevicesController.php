<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    /**
     * POST /api/devices/cli — { id, uuid, user_name?, strategy_name?, device_group_name?,
     * address_book_name?, address_book_tag?, address_book_alias?, address_book_password?,
     * address_book_note?, note?, device_username?, device_name? } + bearer DeployToken.
     *
     * Backs `rustdesk --assign --token …`: register/locate the device and apply the named
     * presets. The client prints any non-empty body verbatim and "Done!" on an empty one
     * (`core_main.rs`), so success returns an empty 200 and failures a plain-text reason.
     */
    public function cli(Request $request): Response
    {
        $token = $this->deployment->resolveToken($request->bearerToken());
        $error = $this->deployment->assign($token, $request->all());

        return response($error)->header('Content-Type', 'text/plain');
    }
}
