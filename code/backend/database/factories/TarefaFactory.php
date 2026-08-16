<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarefa>
 */
class TarefaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'cliente_id' => Cliente::factory(),
            'servico_id' => Servico::factory(),
            'titulo' => 'Reels — Cliente',
            'status' => 'a_fazer',
            'prioridade' => 'media',
            'prazo_em' => now()->addDay(),
            'briefing' => 'Formato 9:16',
            'fase_timer' => null,
            'recorrente' => false,
        ];
    }
}
