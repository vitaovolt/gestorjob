<?php

namespace App\Http\Controllers\Api;

use App\Actions\AtualizarConfiguracao;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguracaoRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->podeVerConfiguracao(), 403);
        $empresa = $user->empresa;
        abort_if($empresa === null, 404);

        return $this->ok([
            'config' => $empresa->config(),
            'editaveis' => $user->chavesConfigEditaveis(),
        ]);
    }

    public function update(UpdateConfiguracaoRequest $request, AtualizarConfiguracao $atualizar): JsonResponse
    {
        $empresa = $request->user()->empresa;
        abort_if($empresa === null, 404);

        $config = $atualizar->handle($empresa, $request->user(), $request->validated());

        return $this->ok([
            'config' => $config,
            'editaveis' => $request->user()->chavesConfigEditaveis(),
            'permissoes' => $request->user()->fresh()->loadMissing('empresa')->permissoesPayload(),
        ], 'Configurações salvas');
    }
}
