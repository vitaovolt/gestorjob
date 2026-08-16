<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarComentarioTarefa;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComentarioRequest;
use App\Models\Tarefa;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ComentarioController extends Controller
{
    use ApiResponse;

    public function store(
        StoreComentarioRequest $request,
        Tarefa $tarefa,
        RegistrarComentarioTarefa $registrar,
    ): JsonResponse {
        $this->authorize('comentar', $tarefa);

        $registrar->handle($tarefa, $request->validated('corpo'), autor: $request->user());

        $tarefa->refresh()->load(['anexos.user']);
        $user = $request->user();
        $tarefa->carregarParaApi(
            $user?->id,
            (bool) $user?->podeVerFinanceiro(),
            true,
        );

        return $this->ok($tarefa, 'Comentário publicado', 201);
    }
}
