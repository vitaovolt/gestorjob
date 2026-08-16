<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IsolamentoTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_nao_enxerga_cliente_de_outra_agencia(): void
    {
        $empresaA = Empresa::factory()->create(['nome' => 'A']);
        $empresaB = Empresa::factory()->create(['nome' => 'B']);

        $adminA = User::factory()->create(['empresa_id' => $empresaA->id, 'papel' => 'admin']);
        User::factory()->create(['empresa_id' => $empresaB->id, 'papel' => 'admin']);

        $clienteB = Cliente::factory()->create([
            'empresa_id' => $empresaB->id,
            'nome_fantasia' => 'Segredo Norte',
        ]);

        Sanctum::actingAs($adminA);

        $lista = $this->getJson('/api/v1/clientes')->assertOk();
        $ids = collect($lista->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($clienteB->id));

        $this->getJson('/api/v1/clientes/'.$clienteB->id)->assertNotFound();
    }

    public function test_mesmo_cnpj_em_tenants_diferentes_e_permitido(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = User::factory()->create(['empresa_id' => $empresaA->id, 'papel' => 'admin']);
        $adminB = User::factory()->create(['empresa_id' => $empresaB->id, 'papel' => 'admin']);

        Sanctum::actingAs($adminA);
        $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'A',
            'cnpj' => '11222333000181',
        ])->assertCreated();

        Sanctum::actingAs($adminB);
        $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'B',
            'cnpj' => '11222333000181',
        ])->assertCreated();

        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_admin_nao_lista_empresas_da_plataforma(): void
    {
        $admin = User::factory()->create(['papel' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/empresas')->assertForbidden();
    }

    public function test_super_admin_lista_empresas(): void
    {
        Empresa::factory()->create(['nome' => 'Agência Educ']);
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $this->getJson('/api/v1/empresas')
            ->assertOk()
            ->assertJsonPath('data.0.nome', 'Agência Educ');
    }

    public function test_lista_de_colaboradores_nao_vaza_outro_tenant(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = User::factory()->create(['empresa_id' => $empresaA->id, 'papel' => 'admin']);
        $adminB = User::factory()->create([
            'empresa_id' => $empresaB->id,
            'papel' => 'admin',
            'email' => 'norte@studio.test',
        ]);

        Sanctum::actingAs($adminA);
        $emails = collect($this->getJson('/api/v1/colaboradores')->assertOk()->json('data'))->pluck('email');
        $this->assertFalse($emails->contains($adminB->email));
        $this->assertTrue($emails->contains($adminA->email));
    }

    public function test_nao_acessa_colaborador_de_outro_tenant(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = User::factory()->create(['empresa_id' => $empresaA->id, 'papel' => 'admin']);
        $adminB = User::factory()->create([
            'empresa_id' => $empresaB->id,
            'papel' => 'admin',
            'email' => 'norte@studio.test',
        ]);

        Sanctum::actingAs($adminA);

        $this->getJson('/api/v1/colaboradores/'.$adminB->id)->assertNotFound();
        $this->putJson('/api/v1/colaboradores/'.$adminB->id, [
            'name' => 'Hack',
            'email' => 'norte@studio.test',
        ])->assertNotFound();
        $this->deleteJson('/api/v1/colaboradores/'.$adminB->id)->assertNotFound();
    }
}
