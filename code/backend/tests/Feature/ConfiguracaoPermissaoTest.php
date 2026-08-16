<?php

namespace Tests\Feature;

use App\Models\Apontamento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfiguracaoPermissaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_config_exige_auth(): void
    {
        $this->getJson('/api/v1/configuracao')->assertUnauthorized();
        $this->putJson('/api/v1/configuracao', ['digest_diario' => true])->assertUnauthorized();
        $this->getJson('/api/v1/permissoes')->assertUnauthorized();
    }

    public function test_colaborador_nao_ve_config_nem_matriz(): void
    {
        $colab = $this->colaboradorDaAgencia()['colab'];
        Sanctum::actingAs($colab);

        $this->getJson('/api/v1/configuracao')->assertForbidden();
        $this->putJson('/api/v1/configuracao', ['digest_diario' => true])->assertForbidden();
        $this->getJson('/api/v1/permissoes')->assertForbidden();
    }

    public function test_admin_persiste_config_no_tenant(): void
    {
        ['admin' => $admin, 'empresa' => $empresa] = $this->colaboradorDaAgencia();
        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/configuracao', [
            'digest_diario' => true,
            'colaborador_cria_tarefas' => true,
            'gerente_exclui_tarefas' => true,
        ])->assertOk()
            ->assertJsonPath('data.config.digest_diario', true)
            ->assertJsonPath('data.config.colaborador_cria_tarefas', true)
            ->assertJsonPath('data.config.colaborador_so_alocadas', true);

        $empresa->refresh();
        $this->assertTrue($empresa->config('digest_diario'));
        $this->assertTrue($empresa->config('colaborador_cria_tarefas'));
        $this->assertTrue($empresa->config('gerente_exclui_tarefas'));
        $this->assertDatabaseHas('empresas', ['id' => $empresa->id]);
        $this->assertNotNull($empresa->configuracao);
    }

    public function test_gerente_so_altera_chaves_de_notificacao(): void
    {
        ['empresa' => $empresa, 'gerente' => $gerente] = $this->colaboradorDaAgencia();
        Sanctum::actingAs($gerente);

        $this->putJson('/api/v1/configuracao', [
            'colaborador_cria_tarefas' => true,
            'digest_diario' => true,
            'notif_email' => false,
        ])->assertOk()
            ->assertJsonPath('data.config.digest_diario', true)
            ->assertJsonPath('data.config.colaborador_cria_tarefas', false);

        $empresa->refresh();
        $this->assertTrue($empresa->config('digest_diario'));
        $this->assertFalse($empresa->config('notif_email'));
        $this->assertFalse($empresa->config('colaborador_cria_tarefas'));
    }

    public function test_colaborador_nao_cria_tarefa_no_padrao_e_cria_com_flag(): void
    {
        ['admin' => $admin, 'empresa' => $empresa, 'colab' => $colab, 'cliente' => $cliente] = $this->colaboradorDaAgencia();
        Sanctum::actingAs($colab);

        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Card da Ana',
        ])->assertForbidden();

        $this->assertDatabaseMissing('tarefas', ['titulo' => 'Card da Ana']);

        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/configuracao', ['colaborador_cria_tarefas' => true])->assertOk();
        $this->assertTrue($empresa->fresh()->config('colaborador_cria_tarefas'));

        Sanctum::actingAs($colab->fresh());
        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Card da Ana',
        ])->assertCreated()
            ->assertJsonPath('data.titulo', 'Card da Ana');

        $this->assertDatabaseHas('tarefas', [
            'titulo' => 'Card da Ana',
            'empresa_id' => $empresa->id,
        ]);
    }

    public function test_colaborador_so_ve_tarefas_alocadas_no_padrao(): void
    {
        ['admin' => $admin, 'empresa' => $empresa, 'colab' => $colab, 'cliente' => $cliente] = $this->colaboradorDaAgencia();
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);

        $alocada = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Minha',
        ]);
        $alocada->responsaveis()->sync([$colab->id]);

        $alheia = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Alheia',
        ]);
        $alheia->responsaveis()->sync([$admin->id]);

        Sanctum::actingAs($colab);
        $lista = $this->getJson('/api/v1/tarefas')->assertOk();
        $ids = collect($lista->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($alocada->id));
        $this->assertFalse($ids->contains($alheia->id));

        $this->getJson("/api/v1/tarefas/{$alheia->id}")->assertNotFound();
        $this->getJson("/api/v1/tarefas/{$alocada->id}")->assertOk();

        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/configuracao', ['colaborador_so_alocadas' => false])->assertOk();

        Sanctum::actingAs($colab->fresh());
        $todas = $this->getJson('/api/v1/tarefas')->assertOk();
        $idsTodas = collect($todas->json('data'))->pluck('id');
        $this->assertTrue($idsTodas->contains($alheia->id));
        $this->getJson("/api/v1/tarefas/{$alheia->id}")->assertOk();
    }

    public function test_gerente_nao_exclui_tarefa_no_padrao_e_exclui_sem_horas_com_flag(): void
    {
        ['admin' => $admin, 'empresa' => $empresa, 'gerente' => $gerente, 'cliente' => $cliente] = $this->colaboradorDaAgencia();
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Sem horas',
        ]);

        Sanctum::actingAs($gerente);
        $this->deleteJson("/api/v1/tarefas/{$tarefa->id}")->assertForbidden();
        $this->assertDatabaseHas('tarefas', ['id' => $tarefa->id]);

        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/configuracao', ['gerente_exclui_tarefas' => true])->assertOk();

        Sanctum::actingAs($gerente->fresh());
        $this->deleteJson("/api/v1/tarefas/{$tarefa->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tarefas', ['id' => $tarefa->id]);
    }

    public function test_nao_exclui_tarefa_com_apontamentos(): void
    {
        ['admin' => $admin, 'empresa' => $empresa, 'cliente' => $cliente] = $this->colaboradorDaAgencia();
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Com horas',
        ]);
        Apontamento::query()->create([
            'empresa_id' => $empresa->id,
            'tarefa_id' => $tarefa->id,
            'user_id' => $admin->id,
            'fase' => 'producao',
            'iniciado_em' => now()->subHour(),
            'encerrado_em' => now(),
            'segundos' => 3600,
            'custo_hora_snapshot' => 90,
        ]);

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/v1/tarefas/{$tarefa->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('tarefas', ['id' => $tarefa->id]);
        $this->assertDatabaseHas('apontamentos', ['tarefa_id' => $tarefa->id]);
    }

    public function test_matriz_de_permissoes_reflete_config(): void
    {
        ['admin' => $admin] = $this->colaboradorDaAgencia();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/permissoes')
            ->assertOk()
            ->assertJsonPath('data.linhas.1.id', 'criar_tarefas')
            ->assertJsonPath('data.linhas.1.celulas.colaborador.tipo', 'config')
            ->assertJsonPath('data.linhas.1.celulas.colaborador.valor', false);

        $this->putJson('/api/v1/configuracao', ['colaborador_cria_tarefas' => true])->assertOk();

        $this->getJson('/api/v1/permissoes')
            ->assertOk()
            ->assertJsonPath('data.linhas.1.celulas.colaborador.valor', true);
    }

    public function test_login_inclui_permissoes(): void
    {
        ['admin' => $admin] = $this->colaboradorDaAgencia();

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.permissoes.criar_tarefas', true)
            ->assertJsonPath('data.user.permissoes.ver_config', true)
            ->assertJsonPath('data.user.permissoes.editar_config', true);
    }

    /**
     * @return array{admin: User, gerente: User, colab: User, empresa: Empresa, cliente: Cliente}
     */
    private function colaboradorDaAgencia(): array
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
            'password' => 'password',
        ]);
        $gerente = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'gerente',
        ]);
        $colab = User::factory()->colaborador()->create([
            'empresa_id' => $empresa->id,
        ]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);

        return compact('admin', 'gerente', 'colab', 'empresa', 'cliente');
    }
}
