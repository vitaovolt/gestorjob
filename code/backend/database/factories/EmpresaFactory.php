<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'plano' => 'starter',
            'limite_usuarios' => 5,
            'status' => 'ativo',
            'wizard_concluido_em' => now(),
        ];
    }

    public function semWizard(): static
    {
        return $this->state(fn () => [
            'wizard_concluido_em' => null,
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn () => [
            'plano' => 'pro',
            'limite_usuarios' => 12,
        ]);
    }
}
