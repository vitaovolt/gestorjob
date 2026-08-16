<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Recorrencia;
use App\Models\Servico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recorrencia>
 */
class RecorrenciaFactory extends Factory
{
    protected $model = Recorrencia::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'cliente_id' => Cliente::factory(),
            'servico_id' => Servico::factory(),
            'titulo' => 'IG 3x — Cliente',
            'responsavel_id' => null,
            'horizonte_semanas' => 4,
            'ativa' => true,
        ];
    }
}
