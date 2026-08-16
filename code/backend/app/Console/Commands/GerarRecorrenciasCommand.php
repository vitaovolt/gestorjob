<?php

namespace App\Console\Commands;

use App\Actions\GerarTodasRecorrencias;
use Illuminate\Console\Command;

class GerarRecorrenciasCommand extends Command
{
    protected $signature = 'gestor:gerar-recorrencias';

    protected $description = 'Gera cards de recorrências ativas no horizonte configurado';

    public function handle(GerarTodasRecorrencias $gerar): int
    {
        $r = $gerar->handle();
        $this->info("Séries: {$r['series']} · Criadas: {$r['criadas']} · Já existiam: {$r['puladas']}");

        return self::SUCCESS;
    }
}
