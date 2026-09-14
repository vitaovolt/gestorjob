<?php

namespace App\Actions;

use App\Models\Tarefa;

class ListarAtrasos
{
    /**
     * @return array{totais: array{no_prazo:int, atrasadas:int, sla:float|null}, tarefas: list<array<string, mixed>>}
     */
    public function handle(): array
    {
        $comPrazo = Tarefa::query()
            ->whereNotNull('prazo_em')
            ->with('cliente')
            ->get();

        $atrasadas = $comPrazo->filter(fn (Tarefa $tarefa) => $tarefa->estaAtrasada())->values();
        $noPrazo = $comPrazo->reject(fn (Tarefa $tarefa) => $tarefa->estaAtrasada());
        $baseSla = $atrasadas->count() + $noPrazo->count();
        $sla = $baseSla > 0 ? round(($noPrazo->count() / $baseSla) * 100, 1) : null;

        return [
            'totais' => [
                'no_prazo' => $noPrazo->count(),
                'atrasadas' => $atrasadas->count(),
                'sla' => $sla,
            ],
            'tarefas' => $atrasadas
                ->sortBy('prazo_em')
                ->values()
                ->map(function (Tarefa $tarefa) {
                    $prazo = $tarefa->prazo_em;

                    return [
                        'id' => $tarefa->id,
                        'titulo' => $tarefa->titulo,
                        'cliente' => $tarefa->cliente?->nome_fantasia,
                        'prazo_em' => $prazo?->toIso8601String(),
                        'dias_atraso' => $prazo
                            ? (int) $prazo->copy()->startOfDay()->diffInDays(now()->startOfDay())
                            : 0,
                    ];
                })
                ->all(),
        ];
    }
}
