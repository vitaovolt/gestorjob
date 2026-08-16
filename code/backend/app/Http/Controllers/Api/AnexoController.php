<?php

namespace App\Http\Controllers\Api;

use App\Actions\AnexarArquivoTarefa;
use App\Actions\ExcluirAnexo;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnexoRequest;
use App\Models\Tarefa;
use App\Models\TarefaAnexo;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    use ApiResponse;

    public function store(StoreAnexoRequest $request, Tarefa $tarefa, AnexarArquivoTarefa $anexar): JsonResponse
    {
        $this->garantirVisivel($request, $tarefa);
        $anexar->handle($tarefa, $request->user(), $request->file('arquivo'));
        $user = $request->user();
        $tarefa->refresh()->load(['anexos.user'])->carregarParaApi(
            $user?->id,
            (bool) $user?->podeVerFinanceiro(),
            true,
        );

        return $this->ok($tarefa, 'Arquivo anexado', 201);
    }

    public function download(Request $request, Tarefa $tarefa, TarefaAnexo $anexo): StreamedResponse
    {
        $this->garantirVisivel($request, $tarefa);
        abort_unless((int) $anexo->tarefa_id === (int) $tarefa->id, 404);
        abort_unless(Storage::disk(TarefaAnexo::DISCO)->exists($anexo->path), 404);

        return Storage::disk(TarefaAnexo::DISCO)->download(
            $anexo->path,
            $anexo->nome_original,
        );
    }

    public function destroy(Request $request, Tarefa $tarefa, TarefaAnexo $anexo, ExcluirAnexo $excluir): JsonResponse
    {
        $this->garantirVisivel($request, $tarefa);
        abort_unless($request->user()?->podeAnexarArquivos(), 403);
        abort_unless((int) $anexo->tarefa_id === (int) $tarefa->id, 404);

        $excluir->handle($anexo);
        $user = $request->user();
        $tarefa->refresh()->load(['anexos.user'])->carregarParaApi(
            $user?->id,
            (bool) $user?->podeVerFinanceiro(),
            true,
        );

        return $this->ok($tarefa, 'Anexo removido');
    }

    private function garantirVisivel(Request $request, Tarefa $tarefa): void
    {
        abort_unless($request->user() !== null && $tarefa->visivelPara($request->user()), 404);
    }
}
