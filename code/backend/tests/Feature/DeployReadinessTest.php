<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeployReadinessTest extends TestCase
{
    public function test_health_inclui_check_de_database(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks.database', 'ok')
            ->assertJsonPath('data.service', 'gestor-job-api');
    }

    public function test_scheduler_tem_avisos_de_prazo(): void
    {
        Artisan::call('schedule:list');
        $out = Artisan::output();

        $this->assertStringContainsString('gestor:avisos-prazo', $out);
        $this->assertStringContainsString('gestor:gerar-recorrencias', $out);
    }

    public function test_env_example_tem_chaves_de_producao(): void
    {
        $env = File::get(base_path('.env.example'));

        foreach (['APP_URL=', 'FRONTEND_URL=', 'DB_CONNECTION=pgsql', 'QUEUE_CONNECTION='] as $needle) {
            $this->assertStringContainsString($needle, $env);
        }
    }

    public function test_workflows_ci_e_deploy_existem_na_raiz(): void
    {
        $root = dirname(base_path(), 2);

        $this->assertFileExists($root.'/.github/workflows/ci.yml');
        $this->assertFileExists($root.'/.github/workflows/deploy.yml');
        $this->assertFileExists($root.'/docs/DEPLOY.md');
    }
}
