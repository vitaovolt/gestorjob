<?php

namespace Tests\Feature;

use App\Models\Apontamento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DominioApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dominio_exige_auth(): void
    {
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
        $this->getJson('/api/v1/servicos')->assertUnauthorized();
        $this->getJson('/api/v1/tarefas')->assertUnauthorized();
        $this->getJson('/api/v1/colaboradores')->assertUnauthorized();
        $this->getJson('/api/v1/empresa')->assertUnauthorized();
        $this->getJson('/api/v1/relatorios/margem')->assertUnauthorized();
    }

    public function test_crud_cliente_servico_e_tarefa_persiste(): void
    {
        $admin = $this->adminDaAgencia();

        $cliente = $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'Educ',
            'cnpj' => '11.222.333/0001-81',
            'fee_mensal' => 8000,
            'status' => 'ativo',
        ]);

        $cliente->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nome_fantasia', 'Educ')
            ->assertJsonPath('data.cnpj', '11222333000181');

        $this->assertDatabaseHas('clientes', [
            'empresa_id' => $admin->empresa_id,
            'nome_fantasia' => 'Educ',
            'cnpj' => '11222333000181',
            'fee_mensal' => 8000,
        ]);

        $clienteId = $cliente->json('data.id');

        $servico = $this->postJson('/api/v1/servicos', [
            'nome' => 'Reels Instagram',
            'preco_venda' => 450,
            'checklist_padrao' => ['Briefing', 'Arte', 'Copy'],
        ]);
        $servico->assertCreated();
        $servicoId = $servico->json('data.id');

        $this->assertDatabaseHas('servicos', [
            'id' => $servicoId,
            'empresa_id' => $admin->empresa_id,
            'nome' => 'Reels Instagram',
        ]);

        $tarefa = $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $clienteId,
            'servico_id' => $servicoId,
            'titulo' => 'Reels — Educ',
            'prioridade' => 'urgente',
            'responsavel_ids' => [$admin->id],
        ]);

        $tarefa->assertCreated()
            ->assertJsonPath('data.titulo', 'Reels — Educ')
            ->assertJsonPath('data.status', 'a_fazer')
            ->assertJsonCount(3, 'data.checklist_itens');

        $tarefaId = $tarefa->json('data.id');
        $this->assertDatabaseHas('tarefas', [
            'id' => $tarefaId,
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $clienteId,
        ]);
        $this->assertDatabaseHas('tarefa_responsaveis', [
            'tarefa_id' => $tarefaId,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('tarefa_checklist_itens', [
            'tarefa_id' => $tarefaId,
            'titulo' => 'Briefing',
            'feito' => false,
        ]);

        $this->putJson("/api/v1/tarefas/{$tarefaId}", ['status' => 'execucao'])
            ->assertOk()
            ->assertJsonPath('data.status', 'execucao');

        $this->assertDatabaseHas('tarefas', ['id' => $tarefaId, 'status' => 'execucao']);

        $this->getJson('/api/v1/tarefas?status=execucao')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/clientes/{$clienteId}", ['fee_mensal' => 9000])
            ->assertOk();
        $this->assertDatabaseHas('clientes', ['id' => $clienteId, 'fee_mensal' => 9000]);

        $this->putJson("/api/v1/servicos/{$servicoId}", ['nome' => 'Reels 9:16', 'preco_venda' => 500])
            ->assertOk();
        $this->assertDatabaseHas('servicos', ['id' => $servicoId, 'nome' => 'Reels 9:16']);

        $this->deleteJson("/api/v1/servicos/{$servicoId}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
        $this->assertDatabaseHas('servicos', ['id' => $servicoId]);
    }

    public function test_empresa_do_tenant_e_colaborador_dentro_do_plano(): void
    {
        $admin = $this->adminDaAgencia();

        $this->getJson('/api/v1/empresa')
            ->assertOk()
            ->assertJsonPath('data.nome', 'Agência Teste')
            ->assertJsonPath('data.limite_usuarios', 12);

        $this->getJson('/api/v1/colaboradores')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', $admin->email);

        $this->postJson('/api/v1/colaboradores', [
            'name' => 'Ana Silva',
            'email' => 'ana@agencia.test',
            'papel' => 'colaborador',
            'custo_hora' => 70,
            'carga_semanal_horas' => 40,
            'departamento' => 'Criação',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'ana@agencia.test');

        $this->assertDatabaseHas('users', [
            'email' => 'ana@agencia.test',
            'empresa_id' => $admin->empresa_id,
            'papel' => 'colaborador',
        ]);
    }

    public function test_admin_ve_margem_do_mes_e_colaborador_nao(): void
    {
        $admin = $this->adminDaAgencia();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'nome_fantasia' => 'Educ',
            'fee_mensal' => 4000,
            'status' => 'ativo',
        ]);
        $servico = Servico::factory()->create(['empresa_id' => $admin->empresa_id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
        ]);

        Apontamento::query()->create([
            'empresa_id' => $admin->empresa_id,
            'tarefa_id' => $tarefa->id,
            'user_id' => $admin->id,
            'fase' => 'producao',
            'iniciado_em' => '2026-08-03 09:00:00',
            'encerrado_em' => '2026-08-03 19:00:00',
            'segundos' => 10 * 3600,
            'custo_hora_snapshot' => 70,
        ]);

        $this->getJson('/api/v1/relatorios/margem?competencia=2026-08')
            ->assertOk()
            ->assertJsonPath('data.competencia', '2026-08')
            ->assertJsonPath('data.clientes.0.nome', 'Educ')
            ->assertJsonPath('data.clientes.0.fee', 4000)
            ->assertJsonPath('data.clientes.0.custo', 700)
            ->assertJsonPath('data.clientes.0.margem', 3300);

        $colaborador = User::factory()->colaborador()->create([
            'empresa_id' => $admin->empresa_id,
        ]);
        Sanctum::actingAs($colaborador);
        $this->getJson('/api/v1/relatorios/margem')->assertForbidden();
        $this->postJson('/api/v1/colaboradores', [
            'name' => 'X',
            'email' => 'x@agencia.test',
        ])->assertForbidden();
    }

    public function test_cnpj_duplicado_no_mesmo_tenant_retorna_422(): void
    {
        $this->adminDaAgencia();

        $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'A',
            'cnpj' => '11222333000181',
        ])->assertCreated();

        $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'B',
            'cnpj' => '11.222.333/0001-81',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    public function test_aceita_cnpj_alfanumerico_e_rejeita_invalido(): void
    {
        $this->adminDaAgencia();

        $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'Alfa',
            'cnpj' => '12.ABC.345/01DE-35',
        ])->assertCreated()
            ->assertJsonPath('data.cnpj', '12ABC34501DE35');

        $this->assertDatabaseHas('clientes', [
            'nome_fantasia' => 'Alfa',
            'cnpj' => '12ABC34501DE35',
        ]);

        $this->postJson('/api/v1/clientes', [
            'nome_fantasia' => 'Ruim',
            'cnpj' => '12.ABC.345/01DE-00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    public function test_colaborador_respeita_limite_de_seats(): void
    {
        $empresa = Empresa::factory()->create(['limite_usuarios' => 1]);
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/colaboradores', [
            'name' => 'Ana',
            'email' => 'ana@agencia.test',
            'papel' => 'colaborador',
            'custo_hora' => 70,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@agencia.test']);
    }

    public function test_seed_agencia_educ_e_studio_norte(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'plataforma@gestorjob.local', 'papel' => 'super_admin']);
        $this->assertDatabaseHas('users', ['email' => 'mariana@agenciaeduc.local']);
        $this->assertDatabaseHas('empresas', ['nome' => 'Agência Educ']);
        $this->assertDatabaseHas('clientes', ['nome_fantasia' => 'Educ']);
        $this->assertDatabaseHas('clientes', ['nome_fantasia' => 'Cliente Norte']);
        $this->assertTrue(Tarefa::query()->where('titulo', 'Reels — Cliente Educ')->exists());
    }

    public function test_visualizador_nao_ve_margem(): void
    {
        $empresa = Empresa::factory()->create();
        $viewer = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'visualizador',
        ]);
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/relatorios/margem')->assertForbidden();
    }

    public function test_nao_exclui_cliente_com_tarefas(): void
    {
        $admin = $this->adminDaAgencia();
        $cliente = Cliente::factory()->create(['empresa_id' => $admin->empresa_id]);
        $servico = Servico::factory()->create(['empresa_id' => $admin->empresa_id]);
        Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
        ]);

        $this->deleteJson("/api/v1/clientes/{$cliente->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
        $this->assertDatabaseHas('tarefas', ['cliente_id' => $cliente->id]);
    }

    public function test_exclui_cliente_sem_tarefas(): void
    {
        $admin = $this->adminDaAgencia();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'nome_fantasia' => 'Só cadastro',
        ]);

        $this->deleteJson("/api/v1/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_nao_exclui_servico_com_tarefas(): void
    {
        $admin = $this->adminDaAgencia();
        $cliente = Cliente::factory()->create(['empresa_id' => $admin->empresa_id]);
        $servico = Servico::factory()->create(['empresa_id' => $admin->empresa_id]);
        Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
        ]);

        $this->deleteJson("/api/v1/servicos/{$servico->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('servicos', ['id' => $servico->id]);
        $this->assertDatabaseHas('tarefas', ['servico_id' => $servico->id]);
    }

    public function test_exclui_servico_sem_tarefas(): void
    {
        $admin = $this->adminDaAgencia();
        $servico = Servico::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'nome' => 'Só cadastro',
        ]);

        $this->deleteJson("/api/v1/servicos/{$servico->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('servicos', ['id' => $servico->id]);
    }

    public function test_atualiza_e_exclui_colaborador_sem_vinculo(): void
    {
        $admin = $this->adminDaAgencia();
        $criado = $this->postJson('/api/v1/colaboradores', [
            'name' => 'Bruno Lima',
            'email' => 'bruno@agencia.test',
            'papel' => 'colaborador',
            'custo_hora' => 55,
            'carga_semanal_horas' => 40,
            'departamento' => 'Mídia',
            'password' => 'senha-inicial-1',
            'password_confirmation' => 'senha-inicial-1',
        ])->assertCreated();

        $id = $criado->json('data.id');
        $this->assertTrue(Hash::check('senha-inicial-1', User::query()->find($id)->password));

        $this->putJson("/api/v1/colaboradores/{$id}", [
            'name' => 'Bruno Lima',
            'email' => 'bruno@agencia.test',
            'papel' => 'colaborador',
            'custo_hora' => 80,
            'carga_semanal_horas' => 30,
            'departamento' => 'Mídia',
            'password' => 'senha-bruno-1',
            'password_confirmation' => 'senha-bruno-1',
        ])->assertOk()
            ->assertJsonPath('data.custo_hora', '80.00');

        $this->assertDatabaseHas('users', [
            'id' => $id,
            'custo_hora' => 80,
            'carga_semanal_horas' => 30,
        ]);
        $this->assertTrue(Hash::check('senha-bruno-1', User::query()->find($id)->password));

        $this->deleteJson("/api/v1/colaboradores/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_nao_exclui_colaborador_com_tarefas(): void
    {
        $admin = $this->adminDaAgencia();
        $pessoa = User::factory()->colaborador()->create([
            'empresa_id' => $admin->empresa_id,
            'email' => 'ana@agencia.test',
        ]);
        $cliente = Cliente::factory()->create(['empresa_id' => $admin->empresa_id]);
        $servico = Servico::factory()->create(['empresa_id' => $admin->empresa_id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
        ]);
        $tarefa->responsaveis()->sync([$pessoa->id]);

        $this->deleteJson("/api/v1/colaboradores/{$pessoa->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $pessoa->id]);
    }

    public function test_nao_exclui_a_propria_conta(): void
    {
        $admin = $this->adminDaAgencia();

        $this->deleteJson("/api/v1/colaboradores/{$admin->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    private function adminDaAgencia(): User
    {
        $empresa = Empresa::factory()->pro()->create(['nome' => 'Agência Teste']);
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }
}
