<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WizardOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_exige_auth(): void
    {
        $this->getJson('/api/v1/wizard')->assertUnauthorized();
        $this->postJson('/api/v1/wizard/concluir')->assertUnauthorized();
    }

    public function test_admin_com_empresa_nova_tem_wizard_pendente(): void
    {
        $empresa = Empresa::factory()->semWizard()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/wizard')
            ->assertOk()
            ->assertJsonPath('data.pendente', true)
            ->assertJsonPath('data.passos.0.titulo', 'Serviços');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.wizard_pendente', true);
    }

    public function test_concluir_wizard_grava_timestamp_e_libera(): void
    {
        $empresa = Empresa::factory()->semWizard()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/wizard/concluir')
            ->assertOk()
            ->assertJsonPath('data.user.wizard_pendente', false)
            ->assertJsonPath('message', 'Onboarding concluído');

        $this->assertNotNull($empresa->fresh()->wizard_concluido_em);
        $this->getJson('/api/v1/wizard')->assertJsonPath('data.pendente', false);
    }

    public function test_colaborador_nao_conclui_wizard(): void
    {
        $empresa = Empresa::factory()->semWizard()->create();
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        Sanctum::actingAs($colab);

        $this->postJson('/api/v1/wizard/concluir')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wizard']);

        $this->assertNull($empresa->fresh()->wizard_concluido_em);
        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.wizard_pendente', false);
    }

    public function test_seed_agencia_educ_ja_concluiu_wizard(): void
    {
        $this->seed();
        $mariana = User::query()->where('email', 'mariana@agenciaeduc.local')->firstOrFail();
        Sanctum::actingAs($mariana);

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.wizard_pendente', false);
    }
}
