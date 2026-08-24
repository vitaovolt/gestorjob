<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupPostgresTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_envia_dump_gzip_e_expurga_antigo(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('gestor_job_20200101_000000.sql.gz', 'x');

        $this->artisan('gestor:backup-postgres')
            ->expectsOutputToContain('Enviado:')
            ->assertSuccessful();

        Storage::disk('backups')->assertMissing('gestor_job_20200101_000000.sql.gz');
        $arquivos = Storage::disk('backups')->files();
        $this->assertCount(1, $arquivos);
        $this->assertTrue(str_ends_with($arquivos[0], '.sql.gz'));
    }
}
