<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AlterarPropriaSenha
{
    public function handle(User $user, string $senhaAtual, string $nova): void
    {
        if (! Hash::check($senhaAtual, $user->password)) {
            throw ValidationException::withMessages([
                'senha_atual' => 'A senha atual não confere.',
            ]);
        }

        $user->update(['password' => $nova]);
    }
}
