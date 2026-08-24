<?php

namespace App\Actions;

use App\Models\Recorrencia;
use App\Models\Tarefa;
use App\Support\Expediente;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class GerarCardsRecorrencia
{
    public function __construct(
        private CriarTarefa $criarTarefa,
    ) {}

    /**
     * @return array{criadas:int,puladas:int}
     */
    public function handle(Recorrencia $recorrencia, mixed $aPartirDe = null): array
    {
        $recorrencia->loadMissing(['servico', 'cliente', 'empresa']);
        $template = $recorrencia->template();
        if (! $template['frequencia']) {
            return ['criadas' => 0, 'puladas' => 0];
        }

        $expediente = Expediente::da($recorrencia->empresa);
        $datas = $this->datasOcorrencia($template, (int) $recorrencia->horizonte_semanas, $expediente, $aPartirDe);
        $criadas = 0;
        $puladas = 0;
        $responsaveis = $recorrencia->idsResponsaveis();

        foreach ($datas as $ocorrencia) {
            $existe = Tarefa::withoutGlobalScopes()
                ->where('recorrencia_id', $recorrencia->id)
                ->whereDate('ocorrencia_em', $ocorrencia->toDateString())
                ->exists();

            if ($existe) {
                $puladas++;

                continue;
            }

            DB::transaction(function () use ($recorrencia, $ocorrencia, $expediente, $responsaveis, &$criadas) {
                $this->criarTarefa->handle([
                    'empresa_id' => $recorrencia->empresa_id,
                    'cliente_id' => $recorrencia->cliente_id,
                    'servico_id' => $recorrencia->servico_id,
                    'titulo' => $recorrencia->titulo,
                    'prazo_em' => $expediente->aplicarHora($ocorrencia),
                    'inicio_em' => $ocorrencia->toDateString(),
                    'briefing' => $recorrencia->briefing ?: 'Gerado por recorrência.',
                    'checklist' => $recorrencia->checklist ?? [],
                    'recorrente' => true,
                    'recorrencia_id' => $recorrencia->id,
                    'ocorrencia_em' => $ocorrencia->toDateString(),
                    'responsavel_ids' => $responsaveis,
                ]);

                $criadas++;
            });
        }

        return ['criadas' => $criadas, 'puladas' => $puladas];
    }

    /**
     * @param  array{frequencia:?string,dias:list<string>}  $template
     * @return list<Carbon>
     */
    private function datasOcorrencia(array $template, int $horizonteSemanas, Expediente $expediente, mixed $aPartirDe = null): array
    {
        $hoje = now()->startOfDay();
        $inicio = $aPartirDe ? Carbon::parse($aPartirDe)->startOfDay() : $hoje->copy();
        if ($inicio->lt($hoje)) {
            $inicio = $hoje->copy();
        }
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
            if (isset(Expediente::DIAS[$dia])) {
                $diasSemana[] = Expediente::DIAS[$dia];
            }
        }
        if ($diasSemana === [] && ($template['frequencia'] ?? '') === 'diaria') {
            $diasSemana = $expediente->diasDaSemanaCarbon();
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
