<?php

namespace Tests\Unit;

use App\Actions\ListarAtrasos;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListarAtrasosTest extends TestCase
{
    use RefreshDatabase;

    public function test_separa_atrasadas_de_no_prazo(): void
    {
        $this->travelTo(Carbon::parse('2026-09-14 12:00:00'));
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'papel' => 'admin']);
        Sanctum::actingAs($admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);

        Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Atrasada',
            'status' => 'a_fazer',
            'prazo_em' => Carbon::parse('2026-09-12 18:00:00'),
        ]);
        Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'No prazo',
            'status' => 'execucao',
            'prazo_em' => Carbon::parse('2026-09-20 18:00:00'),
        ]);

        $resultado = app(ListarAtrasos::class)->handle();

        $this->assertSame(1, $resultado['totais']['atrasadas']);
        $this->assertSame(1, $resultado['totais']['no_prazo']);
        $this->assertSame(50.0, $resultado['totais']['sla']);
        $this->assertSame('Atrasada', $resultado['tarefas'][0]['titulo']);
        $this->assertSame(2, $resultado['tarefas'][0]['dias_atraso']);
    }
}
