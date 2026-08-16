<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AceitarConvite
{
    public function __construct(private GerarConviteAdmin $gerarConvite) {}

    /**
     * @param  array{name: string, password: string}  $dados
     */
    public function handle(string $token, array $dados): User
    {
        $user = $this->gerarConvite->localizar($token);
        if (! $user) {
            throw new HttpException(404, 'Convite inválido ou já usado.');
        }

        if ($user->convite_expira_em === null || $user->convite_expira_em->isPast()) {
            throw ValidationException::withMessages([
                'token' => 'Este convite expirou. Peça um novo link ao Super Admin.',
            ]);
        }

        $user->update([
            'name' => $dados['name'],
            'password' => $dados['password'],
            'convite_token' => null,
            'convite_expira_em' => null,
            'email_verified_at' => now(),
        ]);

        return $user->fresh()->load('empresa');
    }
}
