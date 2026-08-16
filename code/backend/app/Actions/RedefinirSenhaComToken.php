<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedefinirSenhaComToken
{
    public function __construct(
        private SolicitarRecuperacaoSenha $solicitar,
    ) {}

    public function handle(string $token, string $novaSenha): User
    {
        $linha = $this->solicitar->localizar($token);
        if (! $linha) {
            throw ValidationException::withMessages([
                'token' => ['Link inválido ou expirado. Solicite um novo.'],
            ]);
        }

        $user = User::query()->where('email', $linha->email)->first();
        if (! $user || $user->convitePendente()) {
            DB::table('password_reset_tokens')->where('email', $linha->email)->delete();
            throw ValidationException::withMessages([
                'token' => ['Link inválido ou expirado. Solicite um novo.'],
            ]);
        }

        $user->update(['password' => $novaSenha]);
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return $user->fresh();
    }
}
