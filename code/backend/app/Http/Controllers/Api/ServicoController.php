<?php

namespace App\Http\Controllers\Api;

use App\Actions\ExcluirServico;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServicoRequest;
use App\Models\Servico;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServicoController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $servicos = Servico::query()
            ->withCount('tarefas')
            ->orderBy('nome')
            ->get()
            ->each(fn (Servico $s) => $this->ocultarCusto($s, $request->user()));

        return $this->ok($servicos);
    }

    public function store(StoreServicoRequest $request): JsonResponse
    {
        $servico = Servico::query()->create($request->validated());

        return $this->ok($this->ocultarCusto($servico, $request->user()), 'Serviço criado', 201);
    }

    public function show(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        return $this->ok($this->ocultarCusto($servico->loadCount('tarefas'), $request->user()));
    }

    public function update(StoreServicoRequest $request, Servico $servico): JsonResponse
    {
        $servico->update($request->validated());

        return $this->ok($this->ocultarCusto($servico->fresh(), $request->user()), 'Serviço atualizado');
    }

    public function destroy(Servico $servico, ExcluirServico $excluirServico): JsonResponse
    {
        $this->authorize('delete', $servico);

        try {
            $excluirServico->handle($servico);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 409) {
                return $this->fail($e->getMessage(), [], 409);
            }

            throw $e;
        }

        return $this->ok(null, 'Serviço removido');
    }

    private function ocultarCusto(Servico $servico, ?User $user): Servico
    {
        if (! $user?->podeVerFinanceiro()) {
            $servico->makeHidden(['custo_estimado']);
        }

        return $servico;
    }
}
