<?php

namespace App\Actions;

use App\Models\Recorrencia;

class GerarTodasRecorrencias
{
    public function __construct(
        private GerarCardsRecorrencia $gerar,
    ) {}

    /**
     * @return array{series:int,criadas:int,puladas:int}
     */
    public function handle(): array
    {
        $series = 0;
        $criadas = 0;
        $puladas = 0;

        Recorrencia::query()
            ->ativas()
            ->with('servico')
            ->orderBy('id')
            ->each(function (Recorrencia $serie) use (&$series, &$criadas, &$puladas) {
                $series++;
                $r = $this->gerar->handle($serie);
                $criadas += $r['criadas'];
                $puladas += $r['puladas'];
            });

        return compact('series', 'criadas', 'puladas');
    }
}
