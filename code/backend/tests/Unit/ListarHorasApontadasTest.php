<?php

namespace Tests\Unit;

use App\Actions\ListarHorasApontadas;
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

class ListarHorasApontadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_soma_horas_e_compara_com_estimado_do_servico(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id, 'name' => 'Ana']);
        Sanctum::actingAs($user);

        $cliente = Cliente::factory()->create([
            'empresa_id' => $empresa->id,
            'nome_fantasia' => 'Educ',
        ]);
        $servico = Servico::factory()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Folder',
            'tempo_estimado_minutos' => 120,
        ]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
        ]);

        Apontamento::query()->create([
            'empresa_id' => $empresa->id,
            'tarefa_id' => $tarefa->id,
            'user_id' => $user->id,
            'fase' => 'correcao',
            'iniciado_em' => Carbon::parse('2026-08-10 09:00:00'),
            'encerrado_em' => Carbon::parse('2026-08-10 13:00:00'),
            'segundos' => 4 * 3600,
            'custo_hora_snapshot' => 70,
        ]);

        $resultado = app(ListarHorasApontadas::class)->handle(Carbon::parse('2026-08-01'));

        $this->assertSame('2026-08', $resultado['competencia']);
        $this->assertSame('Ana', $resultado['linhas'][0]['colaborador']);
        $this->assertSame('correcao', $resultado['linhas'][0]['fase']);
        $this->assertSame(4.0, $resultado['linhas'][0]['horas']);
        $this->assertSame(280.0, $resultado['linhas'][0]['custo']);
        $this->assertSame(4.0, $resultado['estimado_vs_real'][0]['horas_reais']);
        $this->assertSame(2.0, $resultado['estimado_vs_real'][0]['horas_estimadas']);
        $this->assertSame(200.0, $resultado['estimado_vs_real'][0]['percentual']);
    }
}
