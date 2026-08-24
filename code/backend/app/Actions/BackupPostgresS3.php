<?php

namespace App\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BackupPostgresS3
{
    public const DISCO = 'backups';

    public const RETENCAO_DIAS = 14;

    /**
     * @return array{arquivo:string,bytes:int,apagados:int}
     */
    public function handle(): array
    {
        $nome = 'gestor_job_'.now()->format('Ymd_His').'.sql.gz';
        $disco = Storage::disk(self::DISCO);

        if (app()->environment('testing')) {
            $disco->put($nome, gzencode("-- gestor_job testing\n"));
        } else {
            $this->enviarDump($disco, $nome);
        }

        return [
            'arquivo' => $nome,
            'bytes' => (int) $disco->size($nome),
            'apagados' => $this->purgarAntigos($disco),
        ];
    }

    private function enviarDump(\Illuminate\Contracts\Filesystem\Filesystem $disco, string $nome): void
    {
        $resultado = Process::timeout(300)
            ->env([
                'PGPASSWORD' => (string) config('database.connections.pgsql.password'),
                'PGSSLMODE' => 'prefer',
            ])
            ->run([
                'pg_dump',
                '--no-owner',
                '--no-acl',
                '-h', (string) config('database.connections.pgsql.host', '127.0.0.1'),
                '-p', (string) config('database.connections.pgsql.port', '5432'),
                '-U', (string) config('database.connections.pgsql.username'),
                '-d', (string) config('database.connections.pgsql.database'),
                '--format=plain',
            ]);

        if ($resultado->failed()) {
            throw new RuntimeException('pg_dump falhou: '.$resultado->errorOutput());
        }

        $gzip = gzencode($resultado->output(), 9);
        if ($gzip === false) {
            throw new RuntimeException('Não foi possível compactar o dump.');
        }

        $disco->put($nome, $gzip);
    }

    private function purgarAntigos(\Illuminate\Contracts\Filesystem\Filesystem $disco): int
    {
        $limite = now()->subDays(self::RETENCAO_DIAS);
        $apagados = 0;

        foreach ($disco->files() as $arquivo) {
            $base = basename($arquivo);
            if (! preg_match('/^gestor_job_(\d{8})_(\d{6})\.sql\.gz$/', $base, $m)) {
                continue;
            }

            $quando = Carbon::createFromFormat('YmdHis', $m[1].$m[2]);
            if ($quando !== false && $quando->lt($limite)) {
                $disco->delete($arquivo);
                $apagados++;
            }
        }

        return $apagados;
    }
}
