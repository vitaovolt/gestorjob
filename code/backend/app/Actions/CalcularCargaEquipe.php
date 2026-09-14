<?php

namespace App\Actions;

use App\Models\Apontamento;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class CalcularCargaEquipe
{
    /**
     * @return array{semana_inicio:string, capacidade_media:float|null, colaboradores: list<array<string, mixed>>}
     */
    public function handle(?CarbonInterface $referencia = null): array
    {
        $agora = $referencia ? $referencia->copy() : now();
        $inicio = $agora->copy()->startOfWeek(Carbon::MONDAY);
        $empresaId = auth()->user()?->empresa_id;

        $colaboradores = User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId), fn ($q) => $q->whereRaw('1 = 0'))
            ->whereNull('convite_token')
            ->where('papel', '!=', 'super_admin')
            ->orderBy('name')
            ->get();

        $apontamentos = Apontamento::query()
            ->where(function ($query) use ($inicio, $agora) {
                $query->where(function ($fechados) use ($inicio, $agora) {
                    $fechados->whereNotNull('encerrado_em')
                        ->whereBetween('encerrado_em', [$inicio, $agora]);
                })->orWhere(function ($abertos) use ($agora) {
                    $abertos->whereNull('encerrado_em')
                        ->where('iniciado_em', '<=', $agora);
                });
            })
            ->whereIn('user_id', $colaboradores->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $linhas = $colaboradores->map(function (User $user) use ($apontamentos, $inicio, $agora) {
            $segundos = 0;
            foreach ($apontamentos->get($user->id, collect()) as $apontamento) {
                if ($apontamento->encerrado_em) {
                    $segundos += (int) $apontamento->segundos;

                    continue;
                }

                $inicioContagem = $apontamento->iniciado_em && $apontamento->iniciado_em->gt($inicio)
                    ? $apontamento->iniciado_em
                    : $inicio;
                $segundos += max(0, (int) $inicioContagem->diffInSeconds($agora));
            }

            $horas = round($segundos / 3600, 2);
            $capacidade = $user->carga_semanal_horas;
            $percentual = $capacidade && $capacidade > 0
                ? round(($horas / $capacidade) * 100, 1)
                : null;

            return [
                'user_id' => $user->id,
                'nome' => $user->name,
                'departamento' => $user->departamento,
                'papel' => $user->papel,
                'carga_semanal_horas' => $capacidade,
                'horas' => $horas,
                'percentual' => $percentual,
            ];
        })->values();

        $comPercentual = $linhas->filter(fn (array $linha) => $linha['percentual'] !== null);

        return [
            'semana_inicio' => $inicio->toDateString(),
            'capacidade_media' => $comPercentual->isNotEmpty()
                ? round((float) $comPercentual->avg('percentual'), 1)
                : null,
            'colaboradores' => $linhas->all(),
        ];
    }
}
