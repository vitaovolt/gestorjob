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

class TimerKanbanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_timer_exige_auth(): void
    {
        $tarefa = $this->tarefaDaAgencia()['tarefa'];

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'producao'])
            ->assertUnauthorized();
        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer/pausar")
            ->assertUnauthorized();
    }

    public function test_iniciar_e_pausar_timer_grava_apontamento(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->tarefaDaAgencia();
        Sanctum::actingAs($admin);
        $this->travelTo(now()->startOfSecond());

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'producao'])
            ->assertOk()
            ->assertJsonPath('data.fase_timer', 'producao')
            ->assertJsonPath('data.status', 'execucao')
            ->assertJsonPath('data.timer_aberto.fase', 'producao');

        $this->assertDatabaseHas('apontamentos', [
            'tarefa_id' => $tarefa->id,
            'user_id' => $admin->id,
            'fase' => 'producao',
            'encerrado_em' => null,
        ]);

        $this->travel(45)->seconds();

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer/pausar")
            ->assertOk()
            ->assertJsonPath('data.timer_aberto', null);

        $this->assertGreaterThanOrEqual(45, (int) Apontamento::query()->where('tarefa_id', $tarefa->id)->where('fase', 'producao')->value('segundos'));
        $this->assertNotNull(Apontamento::query()->where('tarefa_id', $tarefa->id)->value('encerrado_em'));
    }

    public function test_retomar_mesma_fase_acumula_e_outra_fase_comeca_do_zero(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->tarefaDaAgencia();
        Sanctum::actingAs($admin);
        $this->travelTo(now()->startOfSecond());

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'producao'])->assertOk();
        $this->travel(45)->seconds();
        $pausado = $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer/pausar")
            ->assertOk()
            ->assertJsonPath('data.timer_aberto', null)
            ->assertJsonPath('data.fase_timer', 'producao');
        $this->assertGreaterThanOrEqual(45, (int) $pausado->json('data.segundos_fase'));

        $retomado = $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'producao'])
            ->assertOk()
            ->assertJsonPath('data.timer_aberto.fase', 'producao');
        $this->assertGreaterThanOrEqual(45, (int) $retomado->json('data.segundos_fase'));
        $this->assertDatabaseCount('apontamentos', 2);

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'revisao'])
            ->assertOk()
            ->assertJsonPath('data.timer_aberto.fase', 'revisao')
            ->assertJsonPath('data.segundos_fase', 0);
    }

    public function test_nao_inicia_segundo_timer_em_outra_tarefa(): void
    {
        ['admin' => $admin, 'empresa' => $empresa, 'cliente' => $cliente, 'servico' => $servico, 'tarefa' => $primeira] = $this->tarefaDaAgencia();
        $segunda = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Outra',
        ]);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/tarefas/{$primeira->id}/timer", ['fase' => 'analise'])->assertOk();
        $this->postJson("/api/v1/tarefas/{$segunda->id}/timer", ['fase' => 'producao'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fase']);

        $this->assertDatabaseCount('apontamentos', 1);
    }

    public function test_trocar_fase_encerra_apontamento_anterior(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->tarefaDaAgencia();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'analise'])->assertOk();
        $this->postJson("/api/v1/tarefas/{$tarefa->id}/timer", ['fase' => 'producao'])->assertOk();

        $this->assertDatabaseHas('apontamentos', [
            'tarefa_id' => $tarefa->id,
            'fase' => 'analise',
        ]);
        $this->assertNotNull(
            Apontamento::query()->where('tarefa_id', $tarefa->id)->where('fase', 'analise')->value('encerrado_em')
        );
        $this->assertDatabaseHas('apontamentos', [
            'tarefa_id' => $tarefa->id,
            'fase' => 'producao',
            'encerrado_em' => null,
        ]);
    }

    public function test_checklist_alterna_feito(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->tarefaDaAgencia();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $tarefa->cliente_id,
            'servico_id' => $tarefa->servico_id,
            'titulo' => 'Com checklist',
        ]);
        $nova = Tarefa::query()->where('titulo', 'Com checklist')->first();
        $item = $nova->checklistItens()->first();
        $this->assertNotNull($item);
        $this->assertFalse((bool) $item->feito);

        $this->putJson("/api/v1/tarefas/{$nova->id}/checklist/{$item->id}", ['feito' => true])
            ->assertOk();

        $this->assertDatabaseHas('tarefa_checklist_itens', [
            'id' => $item->id,
            'feito' => true,
        ]);
    }

    public function test_timer_de_outro_tenant_retorna_404(): void
    {
        ['tarefa' => $tarefaB] = $this->tarefaDaAgencia();
        $empresaA = Empresa::factory()->create();
        $adminA = User::factory()->create(['empresa_id' => $empresaA->id, 'papel' => 'admin']);
        Sanctum::actingAs($adminA);

        $this->postJson("/api/v1/tarefas/{$tarefaB->id}/timer", ['fase' => 'producao'])
            ->assertNotFound();
    }

    /**
     * @return array{admin: User, empresa: Empresa, cliente: Cliente, servico: Servico, tarefa: Tarefa}
     */
    private function tarefaDaAgencia(): array
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
            'custo_hora' => 70,
        ]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'status' => 'a_fazer',
            'titulo' => 'Reels — Educ',
        ]);

        return compact('admin', 'empresa', 'cliente', 'servico', 'tarefa');
    }
}
