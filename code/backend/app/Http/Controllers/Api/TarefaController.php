<?php

namespace App\Http\Controllers\Api;

use App\Actions\CriarTarefa;
use App\Actions\ExcluirTarefa;
use App\Actions\IniciarTimer;
use App\Actions\NotificarResponsaveisTarefa;
use App\Actions\PausarTimer;
use App\Actions\RegistrarComentarioTarefa;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlternarChecklistRequest;
use App\Http\Requests\IniciarTimerRequest;
use App\Http\Requests\StoreTarefaRequest;
use App\Http\Requests\UpdateTarefaRequest;
use App\Models\Notificacao;
use App\Models\Tarefa;
use App\Models\TarefaChecklistItem;
use App\Models\TarefaComentario;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TarefaController extends Controller
{
    use ApiResponse;

    private function flagsApi(Request $request): array
    {
        $user = $request->user();

        return [
            $user?->id,
            (bool) $user?->podeVerFinanceiro(),
            false,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tarefa::class);
        $user = $request->user();
        [$userId, $fin] = $this->flagsApi($request);
        $tarefas = Tarefa::query()
            ->visiveisPara($user)
            ->with(['cliente', 'servico', 'responsaveis', 'checklistItens', 'apontamentosAbertos'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->integer('cliente_id')))
            ->orderBy('prazo_em')
            ->get()
            ->each(fn (Tarefa $tarefa) => $tarefa->carregarParaApi($userId, $fin));

        return $this->ok($tarefas);
    }

    public function store(StoreTarefaRequest $request, CriarTarefa $criarTarefa): JsonResponse
    {
        $tarefa = $criarTarefa->handle($request->validated(), $request->user()?->id);
        [$userId, $fin] = $this->flagsApi($request);
        $tarefa->carregarParaApi($userId, $fin, true);

        return $this->ok($tarefa, 'Tarefa criada', 201);
    }

    public function show(Request $request, Tarefa $tarefa): JsonResponse
    {
        $this->authorize('view', $tarefa);
        [$userId, $fin] = $this->flagsApi($request);
        $tarefa->load(['anexos.user'])->carregarParaApi($userId, $fin, true);

        return $this->ok($tarefa);
    }

    public function update(
        UpdateTarefaRequest $request,
        Tarefa $tarefa,
        NotificarResponsaveisTarefa $notificar,
        RegistrarComentarioTarefa $registrarComentario,
    ): JsonResponse {
        $statusAntes = $tarefa->status;
        $tarefa->update($request->validated());

        if ($request->filled('status') && $statusAntes !== $tarefa->status) {
            $label = NotificarResponsaveisTarefa::labelStatus($tarefa->status);
            $labelAntes = NotificarResponsaveisTarefa::labelStatus($statusAntes);
            $notificar->handle(
                $tarefa->fresh(['responsaveis']),
                Notificacao::TIPO_STATUS_ALTERADO,
                'Movido para '.$label,
                $tarefa->titulo,
                null,
                $request->user()?->id,
            );
            $registrarComentario->handle(
                $tarefa,
                $labelAntes.' → '.$label,
                TarefaComentario::TIPO_SISTEMA,
            );
        }

        [$userId, $fin] = $this->flagsApi($request);
        $tarefa->refresh()->load(['anexos.user'])->carregarParaApi($userId, $fin, true);

        return $this->ok($tarefa, 'Tarefa atualizada');
    }

    public function destroy(Request $request, Tarefa $tarefa, ExcluirTarefa $excluirTarefa): JsonResponse
    {
        $this->authorize('delete', $tarefa);

        try {
            $excluirTarefa->handle($tarefa);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 409) {
                return $this->fail($e->getMessage(), [], 409);
            }

            throw $e;
        }

        return $this->ok(null, 'Tarefa removida');
    }

    public function iniciarTimer(IniciarTimerRequest $request, Tarefa $tarefa, IniciarTimer $iniciarTimer): JsonResponse
    {
        $iniciarTimer->handle($tarefa, $request->user(), $request->validated('fase'));
        [$userId, $fin] = $this->flagsApi($request);
        $tarefa->refresh()->load(['anexos.user'])->carregarParaApi($userId, $fin, true);

        return $this->ok($tarefa, 'Timer iniciado');
    }

    public function pausarTimer(Request $request, Tarefa $tarefa, PausarTimer $pausarTimer): JsonResponse
    {
        $this->authorize('timer', $tarefa);
        $pausarTimer->handle($tarefa, $request->user());
        [$userId, $fin] = $this->flagsApi($request);
        $tarefa->refresh()->load(['anexos.user'])->carregarParaApi($userId, $fin, true);

        return $this->ok($tarefa, 'Timer pausado');
    }

    public function checklist(AlternarChecklistRequest $request, Tarefa $tarefa, TarefaChecklistItem $item): JsonResponse
    {
        abort_unless((int) $item->tarefa_id === (int) $tarefa->id, 404);
        $item->update(['feito' => $request->boolean('feito')]);
        [$userId, $fin] = $this->flagsApi($request);
        $tarefa->refresh()->load(['anexos.user'])->carregarParaApi($userId, $fin, true);

        return $this->ok($tarefa, 'Checklist atualizado');
    }
}
