<?php

namespace Tests\Unit;

use App\Actions\CalcularCargaEquipe;
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

class CalcularCargaEquipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentual_e_horas_da_semana_sobre_capacidade(): void
    {
        $agora = Carbon::parse('2026-09-16 12:00:00');
        $this->travelTo($agora);

        $empresa = Empresa::factory()->create();
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'name' => 'Ana',
            'carga_semanal_horas' => 40,
        ]);
        Sanctum::actingAs($user);

        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
        ]);

        Apontamento::query()->create([
            'empresa_id' => $empresa->id,
            'tarefa_id' => $tarefa->id,
            'user_id' => $user->id,
            'fase' => 'producao',
            'iniciado_em' => Carbon::parse('2026-09-15 09:00:00'),
            'encerrado_em' => Carbon::parse('2026-09-15 17:00:00'),
            'segundos' => 8 * 3600,
            'custo_hora_snapshot' => 70,
        ]);

        $resultado = app(CalcularCargaEquipe::class)->handle($agora);

        $this->assertSame('2026-09-14', $resultado['semana_inicio']);
        $this->assertSame(8.0, $resultado['colaboradores'][0]['horas']);
        $this->assertSame(20.0, $resultado['colaboradores'][0]['percentual']);
        $this->assertSame(20.0, $resultado['capacidade_media']);
    }
}
