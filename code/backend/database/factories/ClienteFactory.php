<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nome_fantasia' => fake()->company(),
            'razao_social' => fake()->company().' LTDA',
            'cnpj' => fake()->unique()->numerify('##############'),
            'segmento' => 'Serviços',
            'status' => 'ativo',
            'contato_nome' => fake()->name(),
            'email' => fake()->companyEmail(),
            'fee_mensal' => 4000,
            'tipo_faturamento' => 'mensal',
            'dia_vencimento' => 10,
        ];
    }
}
