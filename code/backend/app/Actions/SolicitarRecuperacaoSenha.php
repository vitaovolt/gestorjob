<?php

namespace App\Actions;

use App\Mail\RecuperarSenhaMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SolicitarRecuperacaoSenha
{
    /** Minutos de validade do token (alinhado a config/auth.php passwords.users.expire). */
    public const VALIDADE_MINUTOS = 60;

    /**
     * Gera token e enfileira o e-mail. Retorna a URL plaintext (controller só devolve em local/testing).
     *
     * @throws ValidationException se o e-mail não existir ou a conta estiver com convite pendente
     */
    public function handle(string $email): string
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Este e-mail não está cadastrado.'],
            ]);
        }

        if ($user->convitePendente()) {
            throw ValidationException::withMessages([
                'email' => ['Conta ainda não ativada. Use o link do convite.'],
            ]);
        }

        $token = Str::random(48);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ],
        );

        $url = $this->url($token);
        Mail::to($user->email)->queue(new RecuperarSenhaMail($user, $url));

        return $url;
    }

    public function url(string $token): string
    {
        return config('services.frontend.url').'/redefinir-senha?token='.$token;
    }

    public function localizar(string $token): ?object
    {
        if ($token === '') {
            return null;
        }

        $linha = DB::table('password_reset_tokens')
            ->where('token', hash('sha256', $token))
            ->first();

        if (! $linha) {
            return null;
        }

        $criado = $linha->created_at ? \Carbon\Carbon::parse($linha->created_at) : null;
        if (! $criado || $criado->lt(now()->subMinutes(self::VALIDADE_MINUTOS))) {
            DB::table('password_reset_tokens')->where('email', $linha->email)->delete();

            return null;
        }

        return $linha;
    }
}
