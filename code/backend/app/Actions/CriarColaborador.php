<?php

namespace App\Actions;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CriarColaborador
{
    public function handle(Empresa $empresa, array $dados): User
    {
        $ocupados = User::query()->where('empresa_id', $empresa->id)->count();
        if ($ocupados >= $empresa->limite_usuarios) {
            throw ValidationException::withMessages([
                'email' => 'Limite de usuários do plano foi atingido.',
            ]);
        }

        return User::query()->create([
            'empresa_id' => $empresa->id,
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => $dados['password'] ?? 'password',
            'papel' => $dados['papel'] ?? 'colaborador',
            'custo_hora' => $dados['custo_hora'] ?? null,
            'carga_semanal_horas' => $dados['carga_semanal_horas'] ?? null,
            'departamento' => $dados['departamento'] ?? null,
        ]);
    }
}
