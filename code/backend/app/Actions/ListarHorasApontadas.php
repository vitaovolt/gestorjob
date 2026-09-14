<?php

namespace App\Actions;

use App\Models\Apontamento;
use Carbon\CarbonInterface;

class ListarHorasApontadas
{
    /**
     * @return array{competencia:string, linhas: list<array<string, mixed>>, estimado_vs_real: list<array<string, mixed>>}
     */
    public function handle(CarbonInterface $competencia): array
    {
        $inicio = $competencia->copy()->startOfMonth();
        $fim = $competencia->copy()->endOfMonth();

        $apontamentos = Apontamento::query()
            ->with(['user', 'tarefa.cliente', 'tarefa.servico'])
            ->whereNotNull('encerrado_em')
            ->whereBetween('encerrado_em', [$inicio, $fim])
            ->get();

        $linhas = $apontamentos
            ->groupBy(fn (Apontamento $apontamento) => implode('|', [
                (string) $apontamento->user_id,
                (string) ($apontamento->tarefa?->cliente_id ?? '0'),
                (string) $apontamento->fase,
            ]))
            ->map(function ($grupo) {
                $primeiro = $grupo->first();
                $segundos = (int) $grupo->sum('segundos');

                return [
                    'user_id' => $primeiro->user_id,
                    'colaborador' => $primeiro->user?->name,
                    'cliente_id' => $primeiro->tarefa?->cliente_id,
                    'cliente' => $primeiro->tarefa?->cliente?->nome_fantasia,
                    'fase' => $primeiro->fase,
                    'horas' => round($segundos / 3600, 2),
                    'custo' => round($grupo->sum(fn (Apontamento $a) => $a->custo()), 2),
                ];
            })
            ->sortBy([
                ['colaborador', 'asc'],
                ['cliente', 'asc'],
                ['fase', 'asc'],
            ])
            ->values()
            ->all();

        $estimado = $apontamentos
            ->filter(fn (Apontamento $apontamento) => $apontamento->tarefa?->servico_id)
            ->groupBy(fn (Apontamento $apontamento) => implode('|', [
                (string) $apontamento->tarefa->cliente_id,
                (string) $apontamento->tarefa->servico_id,
            ]))
            ->map(function ($grupo) {
                $primeiro = $grupo->first();
                $servico = $primeiro->tarefa?->servico;
                $horasReais = round(((int) $grupo->sum('segundos')) / 3600, 2);
                $minutos = $servico?->tempo_estimado_minutos;
                $horasEstimadas = $minutos ? round($minutos / 60, 2) : null;
                $percentual = $horasEstimadas && $horasEstimadas > 0
                    ? round(($horasReais / $horasEstimadas) * 100, 1)
                    : null;

                return [
                    'cliente' => $primeiro->tarefa?->cliente?->nome_fantasia,
                    'servico' => $servico?->nome,
                    'horas_reais' => $horasReais,
                    'horas_estimadas' => $horasEstimadas,
                    'percentual' => $percentual,
                ];
            })
            ->sortBy([
                ['cliente', 'asc'],
                ['servico', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'competencia' => $inicio->format('Y-m'),
            'linhas' => $linhas,
            'estimado_vs_real' => $estimado,
        ];
    }
}
