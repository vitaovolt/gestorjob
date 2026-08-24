<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Notificacao;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaAnexo;
use App\Models\User;
use App\Support\Expediente;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TarefaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_recorrencia_semanal_nasce_na_tarefa_com_prazo_no_expediente(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00')); // segunda
        ['admin' => $admin, 'cliente' => $cliente] = $this->agencia();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Social DataEduc',
            'repeticao' => ['frequencia' => 'semanal', 'dias' => ['ter', 'qua']],
        ])->assertCreated();

        $tarefas = Tarefa::query()->where('titulo', 'Social DataEduc')->orderBy('ocorrencia_em')->get();
        $this->assertGreaterThan(1, $tarefas->count());
        $primeira = $tarefas->first();
        $this->assertTrue($primeira->recorrente);
        $this->assertNotNull($primeira->recorrencia_id);
        $this->assertSame(18, (int) $primeira->prazo_em->format('H'));
        $this->assertSame($primeira->ocorrencia_em->toDateString(), $primeira->inicio_em->toDateString());
        $this->assertContains($primeira->ocorrencia_em->dayOfWeek, [Carbon::TUESDAY, Carbon::WEDNESDAY]);
        Carbon::setTestNow();
    }

    public function test_diaria_respeita_dias_de_expediente(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21 10:00:00')); // sexta
        ['admin' => $admin, 'cliente' => $cliente] = $this->agencia();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Diária agência',
            'repeticao' => ['frequencia' => 'diaria'],
        ])->assertCreated();

        $dias = Tarefa::query()
            ->where('titulo', 'Diária agência')
            ->pluck('ocorrencia_em')
            ->map(fn ($d) => Carbon::parse($d)->dayOfWeek)
            ->unique()
            ->all();
        $this->assertNotContains(Carbon::SATURDAY, $dias);
        $this->assertNotContains(Carbon::SUNDAY, $dias);
        Carbon::setTestNow();
    }

    public function test_colaborador_nao_aloca_responsaveis(): void
    {
        ['admin' => $admin, 'colab' => $colab, 'cliente' => $cliente, 'empresa' => $empresa] = $this->agencia();
        $empresa->update(['configuracao' => ['colaborador_cria_tarefas' => true]]);
        Sanctum::actingAs($colab->fresh());

        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Sem alocar',
            'responsavel_ids' => [$admin->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['responsavel_ids']);

        Sanctum::actingAs($admin);
        $id = $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Alocada na Ana',
            'responsavel_ids' => [$colab->id],
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($colab);
        $this->putJson("/api/v1/tarefas/{$id}", ['responsavel_ids' => []])->assertUnprocessable();
        $this->deleteJson("/api/v1/tarefas/{$id}")->assertForbidden();
        $this->assertDatabaseHas('tarefas', ['id' => $id]);
    }

    public function test_anexo_notifica_in_app_sem_avisar_quem_anexou(): void
    {
        Storage::fake(TarefaAnexo::DISCO);
        ['admin' => $admin, 'colab' => $colab, 'cliente' => $cliente] = $this->agencia();
        Sanctum::actingAs($admin);
        $id = $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Com anexo',
            'responsavel_ids' => [$admin->id, $colab->id],
        ])->assertCreated()->json('data.id');

        Notificacao::query()->delete();
        $this->post("/api/v1/tarefas/{$id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('arte.png', 8, 8),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertDatabaseHas('notificacoes', [
            'user_id' => $colab->id,
            'tipo' => Notificacao::TIPO_ANEXO,
        ]);
        $this->assertDatabaseMissing('notificacoes', [
            'user_id' => $admin->id,
            'tipo' => Notificacao::TIPO_ANEXO,
        ]);
    }

    public function test_inicio_futuro_some_da_listagem_do_colaborador(): void
    {
        ['admin' => $admin, 'colab' => $colab, 'cliente' => $cliente] = $this->agencia();
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Amanhã',
            'inicio_em' => now()->addDay()->toDateString(),
            'responsavel_ids' => [$colab->id],
        ])->assertCreated();

        Sanctum::actingAs($colab);
        $ids = collect($this->getJson('/api/v1/tarefas')->assertOk()->json('data'))->pluck('titulo');
        $this->assertFalse($ids->contains('Amanhã'));

        Sanctum::actingAs($admin);
        $agendadas = collect($this->getJson('/api/v1/tarefas?visao=agendadas')->json('data'))->pluck('titulo');
        $this->assertTrue($agendadas->contains('Amanhã'));
    }

    public function test_checklist_e_entrega_notificam_in_app(): void
    {
        ['admin' => $admin, 'colab' => $colab, 'cliente' => $cliente] = $this->agencia();
        Sanctum::actingAs($admin);
        $res = $this->postJson('/api/v1/tarefas', [
            'cliente_id' => $cliente->id,
            'titulo' => 'Peça com texto',
            'responsavel_ids' => [$admin->id, $colab->id],
            'checklist' => ['Texto', 'Arte'],
        ])->assertCreated();
        $id = $res->json('data.id');
        $itemId = $res->json('data.checklist_itens.0.id');

        Notificacao::query()->delete();
        $this->putJson("/api/v1/tarefas/{$id}/checklist/{$itemId}", ['feito' => true])->assertOk();
        $this->assertDatabaseHas('notificacoes', [
            'user_id' => $colab->id,
            'tipo' => Notificacao::TIPO_CHECKLIST_ITEM,
        ]);

        Notificacao::query()->delete();
        $this->putJson("/api/v1/tarefas/{$id}", ['status' => 'concluido'])->assertOk();
        $this->assertDatabaseHas('notificacoes', [
            'user_id' => $colab->id,
            'tipo' => Notificacao::TIPO_TAREFA_ENTREGUE,
        ]);
    }

    public function test_servico_nao_exige_recorrencia_e_oculta_custo_do_colaborador(): void
    {
        ['admin' => $admin, 'colab' => $colab] = $this->agencia();
        Sanctum::actingAs($admin);
        $id = $this->postJson('/api/v1/servicos', [
            'nome' => 'Social-mídia',
            'preco_venda' => 900,
            'custo_estimado' => 200,
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($colab);
        $payload = $this->getJson("/api/v1/servicos/{$id}")->assertOk()->json('data');
        $this->assertArrayNotHasKey('custo_estimado', $payload);
    }

    public function test_expediente_aplica_hora_fim(): void
    {
        $empresa = Empresa::factory()->create([
            'configuracao' => ['expediente_hora_fim' => '17:30'],
        ]);
        $exp = Expediente::da($empresa);
        $prazo = $exp->aplicarHora('2026-08-31');
        $this->assertSame('17:30', $prazo->format('H:i'));
    }

    /**
     * @return array{admin: User, colab: User, cliente: Cliente, empresa: Empresa, servico: Servico}
     */
    private function agencia(): array
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);

        return compact('admin', 'colab', 'cliente', 'empresa', 'servico');
    }
}
