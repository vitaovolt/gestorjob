<?php

namespace App\Console\Commands;

use App\Actions\BackupPostgresS3;
use Illuminate\Console\Command;

class BackupPostgresCommand extends Command
{
    protected $signature = 'gestor:backup-postgres';

    protected $description = 'Envia dump gzip do PostgreSQL para s3://gestorjob/postgres';

    public function handle(BackupPostgresS3 $backup): int
    {
        $r = $backup->handle();
        $this->info("Enviado: {$r['arquivo']} ({$r['bytes']} bytes) · apagados: {$r['apagados']}");

        return self::SUCCESS;
    }
}
