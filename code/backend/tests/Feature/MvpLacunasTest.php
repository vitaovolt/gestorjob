<?php

namespace Tests\Feature;

use App\Models\Apontamento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Recorrencia;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MvpLacunasTest extends TestCase
{
    use RefreshDatabase;

    public function test_recorrencia_gera_cards_idempotente(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create([
            'empresa_id' => $empresa->id,
            'recorrencia' => ['frequencia' => 'semanal', 'dias' => ['ter', 'qui'], 'prazo_d_menos' => 1],
        ]);

        Sanctum::actingAs($admin);

        $r1 = $this->postJson('/api/v1/recorrencias', [
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'IG 3x — Educ',
            'horizonte_semanas' => 2,
        ])->assertCreated();

        $criadas = (int) $r1->json('data.geracao.criadas');
        $this->assertGreaterThan(0, $criadas);
        $this->assertDatabaseCount('tarefas', $criadas);

        $serieId = $r1->json('data.recorrencia.id');
        $this->postJson("/api/v1/recorrencias/{$serieId}/gerar")
            ->assertOk()
            ->assertJsonPath('data.geracao.criadas', 0);

        $this->assertDatabaseCount('tarefas', $criadas);
        $this->assertTrue(Tarefa::query()->where('recorrente', true)->exists());
    }

    public function test_comando_gerar_recorrencias(): void
    {
        $empresa = Empresa::factory()->create();
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create([
            'empresa_id' => $empresa->id,
            'recorrencia' => ['frequencia' => 'semanal', 'dias' => ['seg'], 'prazo_d_menos' => 0],
        ]);
        Recorrencia::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Post semanal',
            'horizonte_semanas' => 1,
        ]);

        $this->artisan('gestor:gerar-recorrencias')
            ->expectsOutputToContain('Criadas:')
            ->assertSuccessful();

        $this->assertTrue(Tarefa::withoutGlobalScopes()->where('titulo', 'Post semanal')->exists());
    }

    public function test_admin_ve_custo_e_colaborador_nao(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'custo_hora' => 80]);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id, 'custo_hora' => 70]);
        $tarefa = Tarefa::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa->responsaveis()->sync([$colab->id]);

        Apontamento::query()->create([
            'empresa_id' => $empresa->id,
            'tarefa_id' => $tarefa->id,
            'user_id' => $colab->id,
            'fase' => 'producao',
            'iniciado_em' => now()->subHour(),
            'encerrado_em' => now(),
            'segundos' => 3600,
            'custo_hora_snapshot' => 70,
        ]);

        Sanctum::actingAs($admin);
        $this->getJson("/api/v1/tarefas/{$tarefa->id}")
            ->assertOk()
            ->assertJsonPath('data.custo_acumulado', 70)
            ->assertJsonPath('data.horas_acumuladas', 1);

        Sanctum::actingAs($colab);
        $payload = $this->getJson("/api/v1/tarefas/{$tarefa->id}")->assertOk()->json('data');
        $this->assertArrayNotHasKey('custo_acumulado', $payload);
    }

    public function test_comentario_usuario_e_sistema_no_status(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create(['empresa_id' => $empresa->id, 'status' => 'a_fazer']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/tarefas/{$tarefa->id}/comentarios", [
            'corpo' => 'Arte v1 no Figma',
        ])->assertCreated()
            ->assertJsonPath('data.comentarios.0.corpo', 'Arte v1 no Figma')
            ->assertJsonPath('data.comentarios.0.tipo', 'usuario');

        $this->putJson("/api/v1/tarefas/{$tarefa->id}", ['status' => 'execucao'])
            ->assertOk();

        $this->assertDatabaseHas('tarefa_comentarios', [
            'tarefa_id' => $tarefa->id,
            'tipo' => 'sistema',
            'corpo' => 'A fazer → Em execução',
        ]);
    }

    public function test_visualizador_nao_comenta(): void
    {
        $empresa = Empresa::factory()->create();
        $viewer = User::factory()->visualizador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa->responsaveis()->sync([$viewer->id]);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/tarefas/{$tarefa->id}/comentarios", [
            'corpo' => 'Oi',
        ])->assertForbidden();
    }
}
