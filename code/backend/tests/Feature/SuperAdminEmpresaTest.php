<?php

namespace Tests\Feature;

use App\Mail\ConviteAdminMail;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminEmpresaTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_empresa_exige_auth_e_super_admin(): void
    {
        $this->postJson('/api/v1/empresas', [
            'nome' => 'Nova Agência',
            'plano' => 'pro',
            'limite_usuarios' => 8,
            'admin_nome' => 'Lia Admin',
            'admin_email' => 'lia@nova.test',
        ])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['papel' => 'admin']));

        $this->postJson('/api/v1/empresas', [
            'nome' => 'Nova Agência',
            'plano' => 'pro',
            'limite_usuarios' => 8,
            'admin_nome' => 'Lia Admin',
            'admin_email' => 'lia@nova.test',
        ])->assertForbidden();
    }

    public function test_super_admin_cria_empresa_admin_e_enfileira_convite(): void
    {
        Mail::fake();
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $this->postJson('/api/v1/empresas', [
            'nome' => 'Agência Pixel',
            'plano' => 'pro',
            'limite_usuarios' => 8,
            'admin_nome' => 'Lia Admin',
            'admin_email' => 'lia@pixel.test',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nome', 'Agência Pixel')
            ->assertJsonPath('data.plano', 'pro')
            ->assertJsonPath('data.limite_usuarios', 8)
            ->assertJsonPath('data.admin.email', 'lia@pixel.test')
            ->assertJsonPath('data.admin.convite_pendente', true)
            ->assertJsonPath('data.usuarios_count', 1);

        $url = $this->postJson('/api/v1/empresas', [
            'nome' => 'Studio Leme',
            'plano' => 'starter',
            'limite_usuarios' => 5,
            'admin_nome' => 'Caio Leme',
            'admin_email' => 'caio@leme.test',
        ])->assertCreated()->json('data.convite_url');

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('/convite?token=', $url);

        $this->assertDatabaseHas('empresas', ['nome' => 'Agência Pixel', 'plano' => 'pro']);
        $this->assertDatabaseHas('users', [
            'email' => 'lia@pixel.test',
            'papel' => 'admin',
        ]);
        $this->assertNotNull(User::query()->where('email', 'lia@pixel.test')->value('convite_token'));

        Mail::assertQueued(ConviteAdminMail::class, 2);
    }

    public function test_super_admin_atualiza_empresa_e_admin_nao_acessa_detalhe(): void
    {
        Mail::fake();
        $empresa = Empresa::factory()->create(['nome' => 'Alvo', 'plano' => 'starter']);
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        $super = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);
        $this->getJson("/api/v1/empresas/{$empresa->id}")->assertForbidden();
        $this->putJson("/api/v1/empresas/{$empresa->id}", [
            'nome' => 'Hack',
            'plano' => 'enterprise',
            'limite_usuarios' => 99,
            'status' => 'suspenso',
        ])->assertForbidden();

        Sanctum::actingAs($super);
        $this->putJson("/api/v1/empresas/{$empresa->id}", [
            'nome' => 'Alvo Pro',
            'plano' => 'pro',
            'limite_usuarios' => 15,
            'status' => 'trial',
        ])->assertOk()
            ->assertJsonPath('data.nome', 'Alvo Pro')
            ->assertJsonPath('data.plano', 'pro')
            ->assertJsonPath('data.status', 'trial');

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'nome' => 'Alvo Pro',
            'limite_usuarios' => 15,
            'status' => 'trial',
        ]);
    }

    public function test_reenviar_convite_so_com_pendente(): void
    {
        Mail::fake();
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $id = $this->postJson('/api/v1/empresas', [
            'nome' => 'Reenvio Co',
            'plano' => 'starter',
            'limite_usuarios' => 3,
            'admin_nome' => 'Rita',
            'admin_email' => 'rita@reenvio.test',
        ])->json('data.id');

        $this->postJson("/api/v1/empresas/{$id}/convite")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($this->postJson("/api/v1/empresas/{$id}/convite")->json('data.convite_url'));

        User::query()->where('email', 'rita@reenvio.test')->update([
            'convite_token' => null,
            'convite_expira_em' => null,
        ]);

        $this->postJson("/api/v1/empresas/{$id}/convite")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('empresas', ['id' => $id, 'nome' => 'Reenvio Co']);
    }
}
