<?php

namespace Tests\Unit;

use App\Actions\CalcularMargemCliente;
use App\Models\Apontamento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalcularMargemClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_margem_e_fee_menos_custo_real_do_mes(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id, 'custo_hora' => 70]);
        $cliente = Cliente::factory()->create([
            'empresa_id' => $empresa->id,
            'fee_mensal' => 4000,
        ]);
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
            'iniciado_em' => Carbon::parse('2026-08-03 09:00:00'),
            'encerrado_em' => Carbon::parse('2026-08-03 19:00:00'),
            'segundos' => 10 * 3600,
            'custo_hora_snapshot' => 70,
        ]);

        $resultado = app(CalcularMargemCliente::class)->handle($cliente, Carbon::parse('2026-08-01'));

        $this->assertSame(10.0, $resultado['horas']);
        $this->assertSame(700.0, $resultado['custo']);
        $this->assertSame(4000.0, $resultado['fee']);
        $this->assertSame(3300.0, $resultado['margem']);
        $this->assertSame(82.5, $resultado['margem_percentual']);
    }
}
