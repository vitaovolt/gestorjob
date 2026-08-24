<?php

namespace App\Http\Controllers\Api;

use App\Actions\GerarCardsRecorrencia;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecorrenciaRequest;
use App\Models\Recorrencia;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecorrenciaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Recorrencia::class);

        $lista = Recorrencia::query()
            ->with(['cliente', 'servico', 'responsavel'])
            ->when($request->filled('servico_id'), fn ($q) => $q->where('servico_id', $request->integer('servico_id')))
            ->orderByDesc('id')
            ->get();

        return $this->ok($lista);
    }

    public function store(StoreRecorrenciaRequest $request, GerarCardsRecorrencia $gerar): JsonResponse
    {
        $dados = $request->validated();
        $ids = array_values(array_map('intval', $dados['responsavel_ids'] ?? []));
        if ($ids === [] && isset($dados['responsavel_id'])) {
            $ids = [(int) $dados['responsavel_id']];
        }

        $serie = Recorrencia::query()->create([
            'empresa_id' => $request->user()->empresa_id,
            'cliente_id' => $dados['cliente_id'],
            'servico_id' => $dados['servico_id'] ?? null,
            'titulo' => $dados['titulo'],
            'briefing' => $dados['briefing'] ?? null,
            'frequencia' => $dados['frequencia'],
            'dias' => $dados['dias'] ?? [],
            'responsavel_id' => $ids[0] ?? ($dados['responsavel_id'] ?? null),
            'responsavel_ids' => $ids,
            'horizonte_semanas' => $dados['horizonte_semanas'] ?? 4,
            'ativa' => true,
        ]);

        $resultado = $gerar->handle($serie->fresh(['servico', 'cliente', 'empresa']));

        return $this->ok([
            'recorrencia' => $serie->load(['cliente', 'servico', 'responsavel']),
            'geracao' => $resultado,
        ], 'Recorrência ativada', 201);
    }

    public function gerar(Recorrencia $recorrencia, GerarCardsRecorrencia $gerar): JsonResponse
    {
        $this->authorize('update', $recorrencia);
        abort_unless($recorrencia->ativa, 422, 'Recorrência inativa.');

        $resultado = $gerar->handle($recorrencia);

        return $this->ok([
            'recorrencia' => $recorrencia->load(['cliente', 'servico', 'responsavel']),
            'geracao' => $resultado,
        ], 'Cards gerados');
    }

    public function destroy(Recorrencia $recorrencia): JsonResponse
    {
        $this->authorize('delete', $recorrencia);
        $recorrencia->update(['ativa' => false]);

        return $this->ok($recorrencia->fresh(), 'Recorrência desativada');
    }
}
