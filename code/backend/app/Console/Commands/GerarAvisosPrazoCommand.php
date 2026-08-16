<?php

namespace App\Console\Commands;

use App\Actions\GerarAvisosPrazoHoje;
use Illuminate\Console\Command;

class GerarAvisosPrazoCommand extends Command
{
    protected $signature = 'gestor:avisos-prazo';

    protected $description = 'Gera avisos in-app e e-mails de prazo para hoje';

    public function handle(GerarAvisosPrazoHoje $gerar): int
    {
        $r = $gerar->handle();
        $this->info("In-app: {$r['in_app']} · E-mails: {$r['emails']}");

        return self::SUCCESS;
    }
}
