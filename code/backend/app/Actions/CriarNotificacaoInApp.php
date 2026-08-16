<?php

namespace App\Actions;

use App\Models\Empresa;
use App\Models\Notificacao;
use App\Models\User;

class CriarNotificacaoInApp
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function handle(
        User $destinatario,
        string $tipo,
        string $titulo,
        ?string $corpo = null,
        array $dados = [],
    ): ?Notificacao {
        if ($destinatario->ehSuperAdmin() || ! $destinatario->empresa_id) {
            return null;
        }

        $empresa = $destinatario->empresa ?? Empresa::query()->find($destinatario->empresa_id);
        if (! $empresa || ! $empresa->config('notif_in_app')) {
            return null;
        }

        return Notificacao::query()->create([
            'empresa_id' => $destinatario->empresa_id,
            'user_id' => $destinatario->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'corpo' => $corpo,
            'dados' => $dados === [] ? null : $dados,
        ]);
    }
}
