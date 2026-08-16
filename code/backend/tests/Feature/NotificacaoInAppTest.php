<?php

namespace Tests\Feature;

use App\Actions\GerarAvisosPrazoHoje;
use App\Models\Empresa;
use App\Models\Notificacao;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificacaoInAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_exige_auth(): void
    {
        $this->getJson('/api/v1/notificacoes')->assertUnauthorized();
    }

    public function test_mover_status_notifica_responsavel(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'status' => 'execucao',
            'titulo' => 'Reels teste',
        ]);
        $tarefa->responsaveis()->sync([$colab->id, $admin->id]);

        Sanctum::actingAs($admin);
        $this->putJson("/api/v1/tarefas/{$tarefa->id}", ['status' => 'revisao'])
            ->assertOk();

        $this->assertDatabaseHas('notificacoes', [
            'user_id' => $colab->id,
            'tipo' => Notificacao::TIPO_STATUS_ALTERADO,
            'titulo' => 'Movido para Em revisão',
        ]);
        $this->assertDatabaseMissing('notificacoes', [
            'user_id' => $admin->id,
            'tipo' => Notificacao::TIPO_STATUS_ALTERADO,
        ]);
    }

    public function test_criar_tarefa_notifica_alocados(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $cliente = \App\Models\Cliente::factory()->create(['empresa_id' => $empresa->id]);

        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Nova alocada',
            'responsavel_ids' => [$colab->id, $admin->id],
        ])->assertCreated();

        $this->assertDatabaseHas('notificacoes', [
            'user_id' => $colab->id,
            'tipo' => Notificacao::TIPO_TAREFA_ALOCADA,
            'titulo' => 'Você foi alocado',
        ]);
        $this->assertDatabaseMissing('notificacoes', [
            'user_id' => $admin->id,
            'tipo' => Notificacao::TIPO_TAREFA_ALOCADA,
        ]);
    }

    public function test_respeita_flag_notif_in_app(): void
    {
        $empresa = Empresa::factory()->create([
            'configuracao' => ['notif_in_app' => false],
        ]);
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create(['empresa_id' => $empresa->id, 'status' => 'a_fazer']);
        $tarefa->responsaveis()->sync([$colab->id]);

        Sanctum::actingAs($admin);
        $this->putJson("/api/v1/tarefas/{$tarefa->id}", ['status' => 'execucao'])->assertOk();

        $this->assertDatabaseCount('notificacoes', 0);
    }

    public function test_marcar_lida_e_contador(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $n = Notificacao::query()->create([
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'tipo' => Notificacao::TIPO_PRAZO_HOJE,
            'titulo' => 'Prazo hoje',
            'corpo' => 'Reels',
            'dados' => ['tarefa_id' => 1],
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/notificacoes/nao-lidas')
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $this->postJson("/api/v1/notificacoes/{$n->id}/lida")
            ->assertOk()
            ->assertJsonPath('data.lida', true);

        $this->getJson('/api/v1/notificacoes/nao-lidas')
            ->assertJsonPath('data.total', 0);
    }

    public function test_outro_usuario_nao_le_notificacao_alheia(): void
    {
        $empresa = Empresa::factory()->create();
        $a = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $b = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $n = Notificacao::query()->create([
            'empresa_id' => $empresa->id,
            'user_id' => $a->id,
            'tipo' => Notificacao::TIPO_PRAZO_HOJE,
            'titulo' => 'Prazo hoje',
        ]);

        Sanctum::actingAs($b);
        $this->postJson("/api/v1/notificacoes/{$n->id}/lida")->assertNotFound();
    }

    public function test_gerar_avisos_prazo_hoje(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $empresa = Empresa::factory()->create();
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Prazo Reels',
            'status' => 'execucao',
            'prazo_em' => now()->setTime(18, 0),
        ]);
        $tarefa->responsaveis()->sync([$colab->id]);

        $n = app(GerarAvisosPrazoHoje::class)->handle();
        $this->assertSame(1, $n['in_app']);
        $this->assertSame(1, $n['emails']);
        $this->assertDatabaseHas('notificacoes', [
            'user_id' => $colab->id,
            'tipo' => Notificacao::TIPO_PRAZO_HOJE,
            'titulo' => 'Prazo hoje',
        ]);

        $segunda = app(GerarAvisosPrazoHoje::class)->handle();
        $this->assertSame(0, $segunda['in_app']);
        $this->assertSame(0, $segunda['emails']);
    }
}
