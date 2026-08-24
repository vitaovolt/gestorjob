<?php

namespace App\Actions;

use App\Models\Recorrencia;
use App\Models\Tarefa;
use App\Models\User;
use App\Support\Expediente;
use Illuminate\Validation\ValidationException;

class LancarTarefa
{
    public function __construct(
        private CriarTarefa $criarTarefa,
        private GerarCardsRecorrencia $gerarCards,
    ) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function handle(array $dados, User $ator): Tarefa
    {
        $ator->loadMissing('empresa');
        $expediente = Expediente::da($ator->empresa);
        $dados['empresa_id'] = $ator->empresa_id;
        $dados['prazo_em'] = $expediente->aplicarHora($dados['prazo_em'] ?? null);
        $dados['inicio_em'] = $dados['inicio_em'] ?? now()->toDateString();
        unset($dados['status']);

        $repeticao = $dados['repeticao'] ?? [];
        $freq = $repeticao['frequencia'] ?? 'nunca';
        $diasReq = array_values(array_filter(array_map('strval', $repeticao['dias'] ?? [])));
        unset($dados['repeticao']);

        if (! in_array($freq, ['diaria', 'semanal'], true)) {
            return $this->criarTarefa->handle($dados, $ator->id);
        }

        $dias = $freq === 'diaria' ? $expediente->dias() : $diasReq;

        if ($freq === 'semanal' && $dias === []) {
            throw ValidationException::withMessages([
                'repeticao.dias' => ['Informe os dias da semana.'],
            ]);
        }

        $ids = array_values(array_map('intval', $dados['responsavel_ids'] ?? []));

        $serie = Recorrencia::query()->create([
            'empresa_id' => $ator->empresa_id,
            'cliente_id' => $dados['cliente_id'],
            'servico_id' => $dados['servico_id'] ?? null,
            'titulo' => $dados['titulo'],
            'briefing' => $dados['briefing'] ?? null,
            'checklist' => $dados['checklist'] ?? [],
            'frequencia' => $freq,
            'dias' => $dias,
            'responsavel_id' => $ids[0] ?? null,
            'responsavel_ids' => $ids,
            'horizonte_semanas' => 4,
            'ativa' => true,
        ]);

        $this->gerarCards->handle(
            $serie->fresh(['servico', 'cliente', 'empresa']),
            $dados['inicio_em'] ?? null,
        );

        $primeira = Tarefa::query()
            ->where('recorrencia_id', $serie->id)
            ->orderBy('ocorrencia_em')
            ->first();

        if ($primeira) {
            return $primeira;
        }

        return $this->criarTarefa->handle($dados, $ator->id);
    }
}
