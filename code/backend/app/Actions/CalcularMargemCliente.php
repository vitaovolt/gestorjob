<?php

namespace App\Actions;

use App\Models\Apontamento;
use App\Models\Cliente;
use Carbon\CarbonInterface;

class CalcularMargemCliente
{
    /**
     * Fee − (Σ horas × custo/hora) no mês da competência.
     *
     * @return array{cliente_id:int, fee:float, horas:float, custo:float, margem:float, margem_percentual:float|null}
     */
    public function handle(Cliente $cliente, CarbonInterface $competencia): array
    {
        $inicio = $competencia->copy()->startOfMonth();
        $fim = $competencia->copy()->endOfMonth();

        $apontamentos = Apontamento::query()
            ->whereHas('tarefa', fn ($q) => $q->where('cliente_id', $cliente->id))
            ->whereNotNull('encerrado_em')
            ->whereBetween('encerrado_em', [$inicio, $fim])
            ->get();

        $segundos = (int) $apontamentos->sum('segundos');
        $custo = round($apontamentos->sum(fn (Apontamento $a) => $a->custo()), 2);
        $horas = round($segundos / 3600, 2);
        $fee = (float) $cliente->fee_mensal;
        $margem = round($fee - $custo, 2);
        $percentual = $fee > 0 ? round(($margem / $fee) * 100, 1) : null;

        return [
            'cliente_id' => $cliente->id,
            'nome' => $cliente->nome_fantasia,
            'fee' => $fee,
            'horas' => $horas,
            'custo' => $custo,
            'margem' => $margem,
            'margem_percentual' => $percentual,
        ];
    }
}
