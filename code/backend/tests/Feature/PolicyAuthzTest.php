<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PolicyAuthzTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonimo_recebe_401_em_recursos_protegidos(): void
    {
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
        $this->postJson('/api/v1/clientes', ['nome_fantasia' => 'X'])->assertUnauthorized();
        $this->getJson('/api/v1/servicos')->assertUnauthorized();
        $this->getJson('/api/v1/tarefas')->assertUnauthorized();
        $this->getJson('/api/v1/empresas')->assertUnauthorized();
    }

    public function test_visualizador_nao_cria_nem_atualiza_cliente(): void
    {
        $empresa = Empresa::factory()->create();
        $viewer = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'visualizador',
        ]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);

        Sanctum::actingAs($viewer);

        $this->postJson('/api/v1/clientes', ['nome_fantasia' => 'Hack'])
            ->assertForbidden();

        $this->putJson("/api/v1/clientes/{$cliente->id}", ['nome_fantasia' => 'Hack'])
            ->assertForbidden();

        $this->deleteJson("/api/v1/clientes/{$cliente->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('clientes', ['nome_fantasia' => 'Hack']);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'nome_fantasia' => $cliente->nome_fantasia]);
    }

    public function test_visualizador_nao_muta_servico_nem_tarefa(): void
    {
        $empresa = Empresa::factory()->create();
        $viewer = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'visualizador',
        ]);
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'status' => 'execucao',
        ]);
        $tarefa->responsaveis()->sync([$viewer->id]);

        Sanctum::actingAs($viewer);

        $this->postJson('/api/v1/servicos', ['nome' => 'Hack'])
            ->assertForbidden();
        $this->putJson("/api/v1/servicos/{$servico->id}", ['nome' => 'Hack'])
            ->assertForbidden();
        $this->deleteJson("/api/v1/servicos/{$servico->id}")
            ->assertForbidden();

        $this->putJson("/api/v1/tarefas/{$tarefa->id}", ['status' => 'revisao'])
            ->assertForbidden();
        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'producao'])
            ->assertForbidden();

        $this->assertDatabaseHas('tarefas', ['id' => $tarefa->id, 'status' => 'execucao']);
    }

    public function test_colaborador_pode_mover_status_mas_nao_cria_cliente(): void
    {
        $empresa = Empresa::factory()->create();
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'status' => 'a_fazer',
        ]);
        $tarefa->responsaveis()->sync([$colab->id]);

        Sanctum::actingAs($colab);

        $this->postJson('/api/v1/clientes', ['nome_fantasia' => 'Sem permissão'])
            ->assertForbidden();

        $this->putJson("/api/v1/tarefas/{$tarefa->id}", ['status' => 'execucao'])
            ->assertOk();

        $this->assertDatabaseHas('tarefas', ['id' => $tarefa->id, 'status' => 'execucao']);
    }

    public function test_admin_tenant_nao_lista_empresas_plataforma(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/empresas')->assertForbidden();
    }
}
