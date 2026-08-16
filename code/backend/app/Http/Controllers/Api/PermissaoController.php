<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\ConfiguracaoTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissaoController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->podeVerConfiguracao(), 403);
        $empresa = $user->empresa;
        abort_if($empresa === null, 404);

        return $this->ok(ConfiguracaoTenant::matriz($empresa));
    }
}
