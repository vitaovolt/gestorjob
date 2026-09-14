<?php

namespace Tests\Feature;

use App\Models\Apontamento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RelatoriosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_relatorios_exigem_auth(): void
    {
        $this->getJson('/api/v1/relatorios/margem')->assertUnauthorized();
        $this->getJson('/api/v1/relatorios/atrasos')->assertUnauthorized();
        $this->getJson('/api/v1/relatorios/horas')->assertUnauthorized();
        $this->getJson('/api/v1/relatorios/carga')->assertUnauthorized();
    }

    public function test_colaborador_e_visualizador_recebem_403(): void
    {
        $empresa = Empresa::factory()->create();

        Sanctum::actingAs(User::factory()->colaborador()->create(['empresa_id' => $empresa->id]));
        $this->getJson('/api/v1/relatorios/atrasos')->assertForbidden();
        $this->getJson('/api/v1/relatorios/horas')->assertForbidden();
        $this->getJson('/api/v1/relatorios/carga')->assertForbidden();

        Sanctum::actingAs(User::factory()->visualizador()->create(['empresa_id' => $empresa->id]));
        $this->getJson('/api/v1/relatorios/atrasos')->assertForbidden();
        $this->getJson('/api/v1/relatorios/horas')->assertForbidden();
        $this->getJson('/api/v1/relatorios/carga')->assertForbidden();
    }

    public function test_atrasos_lista_abertas_vencidas_e_calcula_sla(): void
    {
        $this->travelTo(Carbon::parse('2026-09-14 15:00:00'));
        $admin = $this->adminDaAgencia();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'nome_fantasia' => 'Educ',
        ]);
        $servico = Servico::factory()->create(['empresa_id' => $admin->empresa_id]);

        Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Copy atrasada Educ',
            'status' => 'a_fazer',
            'prazo_em' => Carbon::parse('2026-09-13 18:00:00'),
        ]);
        Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'No prazo',
            'status' => 'execucao',
            'prazo_em' => Carbon::parse('2026-09-16 18:00:00'),
        ]);
        Tarefa::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Já concluída',
            'status' => 'concluido',
            'prazo_em' => Carbon::parse('2026-09-10 18:00:00'),
        ]);

        $this->getJson('/api/v1/relatorios/atrasos')
            ->assertOk()
            ->assertJsonPath('data.totais.atrasadas', 1)
            ->assertJsonPath('data.totais.no_prazo', 2)
            ->assertJsonPath('data.totais.sla', 66.7)
            ->assertJsonPath('data.tarefas.0.titulo', 'Copy atrasada Educ')
            ->assertJsonPath('data.tarefas.0.cliente', 'Educ')
            ->assertJsonPath('data.tarefas.0.dias_atraso', 1);
    }

    public function test_horas_agrupa_por_colaborador_cliente_e_fase(): void
    {
        $admin = $this->adminDaAgencia();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'nome_fantasia' => 'Educ',
        ]);
        $servico = Servico::factory()->create([
            'empresa_id' => $admin->empresa_id,
            'nome' => 'Folder',
            'tempo_estimado_minutos' => 180,
        ]);
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

        $this->getJson('/api/v1/relatorios/horas?competencia=2026-08')
            ->assertOk()
            ->assertJsonPath('data.competencia', '2026-08')
            ->assertJsonPath('data.linhas.0.cliente', 'Educ')
            ->assertJsonPath('data.linhas.0.fase', 'producao')
            ->assertJsonPath('data.linhas.0.horas', 10)
            ->assertJsonPath('data.linhas.0.custo', 700)
            ->assertJsonPath('data.estimado_vs_real.0.servico', 'Folder')
            ->assertJsonPath('data.estimado_vs_real.0.horas_reais', 10)
            ->assertJsonPath('data.estimado_vs_real.0.horas_estimadas', 3)
            ->assertJsonPath('data.estimado_vs_real.0.percentual', 333.3);
    }

    public function test_carga_usa_horas_da_semana_e_capacidade(): void
    {
        $this->travelTo(Carbon::parse('2026-09-14 18:00:00'));
        $admin = $this->adminDaAgencia();
        $admin->update(['carga_semanal_horas' => 40]);

        $cliente = Cliente::factory()->create(['empresa_id' => $admin->empresa_id]);
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
            'iniciado_em' => '2026-09-14 08:00:00',
            'encerrado_em' => '2026-09-14 18:00:00',
            'segundos' => 10 * 3600,
            'custo_hora_snapshot' => 70,
        ]);

        $this->getJson('/api/v1/relatorios/carga')
            ->assertOk()
            ->assertJsonPath('data.semana_inicio', '2026-09-14')
            ->assertJsonPath('data.colaboradores.0.horas', 10)
            ->assertJsonPath('data.colaboradores.0.percentual', 25)
            ->assertJsonPath('data.capacidade_media', 25);
    }

    private function adminDaAgencia(): User
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
            'carga_semanal_horas' => 40,
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }
}
