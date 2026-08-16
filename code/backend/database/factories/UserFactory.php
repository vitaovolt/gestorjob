<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'papel' => 'admin',
            'custo_hora' => 70,
            'carga_semanal_horas' => 40,
            'departamento' => 'Criação',
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'empresa_id' => null,
            'papel' => 'super_admin',
            'custo_hora' => null,
            'departamento' => 'Plataforma',
        ]);
    }

    public function colaborador(): static
    {
        return $this->state(fn () => [
            'papel' => 'colaborador',
        ]);
    }

    public function visualizador(): static
    {
        return $this->state(fn () => [
            'papel' => 'visualizador',
            'custo_hora' => null,
        ]);
    }
}
