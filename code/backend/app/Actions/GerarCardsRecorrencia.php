<?php

namespace App\Actions;

use App\Models\Recorrencia;
use App\Models\Tarefa;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class GerarCardsRecorrencia
{
    private const DIAS = [
        'dom' => Carbon::SUNDAY,
        'seg' => Carbon::MONDAY,
        'ter' => Carbon::TUESDAY,
        'qua' => Carbon::WEDNESDAY,
        'qui' => Carbon::THURSDAY,
        'sex' => Carbon::FRIDAY,
        'sab' => Carbon::SATURDAY,
    ];

    public function __construct(
        private CriarTarefa $criarTarefa,
    ) {}

    /**
     * @return array{criadas:int,puladas:int}
     */
    public function handle(Recorrencia $recorrencia): array
    {
        $recorrencia->loadMissing(['servico', 'cliente']);
        $template = $recorrencia->template();
        if (! $template['frequencia']) {
            return ['criadas' => 0, 'puladas' => 0];
        }

        $datas = $this->datasOcorrencia($template, (int) $recorrencia->horizonte_semanas);
        $criadas = 0;
        $puladas = 0;
        $dMenos = $template['prazo_d_menos'];

        foreach ($datas as $ocorrencia) {
            $existe = Tarefa::withoutGlobalScopes()
                ->where('recorrencia_id', $recorrencia->id)
                ->whereDate('ocorrencia_em', $ocorrencia->toDateString())
                ->exists();

            if ($existe) {
                $puladas++;

                continue;
            }

            DB::transaction(function () use ($recorrencia, $ocorrencia, $dMenos, &$criadas) {
                $this->criarTarefa->handle([
                    'empresa_id' => $recorrencia->empresa_id,
                    'cliente_id' => $recorrencia->cliente_id,
                    'servico_id' => $recorrencia->servico_id,
                    'titulo' => $recorrencia->titulo,
                    'prazo_em' => $ocorrencia->copy()->setTime(18, 0),
                    'briefing' => 'Gerado por recorrência · D-'.$dMenos.'.',
                    'recorrente' => true,
                    'recorrencia_id' => $recorrencia->id,
                    'ocorrencia_em' => $ocorrencia->toDateString(),
                    'responsavel_ids' => $recorrencia->responsavel_id
                        ? [(int) $recorrencia->responsavel_id]
                        : [],
                ]);

                $criadas++;
            });
        }

        return ['criadas' => $criadas, 'puladas' => $puladas];
    }

    /**
     * @param  array{frequencia:?string,dias:list<string>,prazo_d_menos:int}  $template
     * @return list<Carbon>
     */
    private function datasOcorrencia(array $template, int $horizonteSemanas): array
    {
        $inicio = now()->startOfDay();
        $fim = $inicio->copy()->addWeeks(max(1, $horizonteSemanas))->endOfDay();
        $datas = [];

        if (($template['frequencia'] ?? '') === 'mensal') {
            $cursor = $inicio->copy()->startOfMonth();
            if ($cursor->lt($inicio)) {
                $cursor->addMonth();
            }
            while ($cursor->lte($fim)) {
                if ($cursor->gte($inicio)) {
                    $datas[] = $cursor->copy();
                }
                $cursor->addMonth();
            }

            return $datas;
        }

        $diasSemana = [];
        foreach ($template['dias'] as $dia) {
            if (isset(self::DIAS[$dia])) {
                $diasSemana[] = self::DIAS[$dia];
            }
        }
        if ($diasSemana === []) {
            return [];
        }

        foreach (CarbonPeriod::create($inicio, $fim) as $dia) {
            /** @var Carbon $dia */
            if (in_array($dia->dayOfWeek, $diasSemana, true)) {
                $datas[] = $dia->copy()->startOfDay();
            }
        }

        return $datas;
    }
}
