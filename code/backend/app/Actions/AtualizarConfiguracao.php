<?php

namespace App\Actions;

use App\Models\Empresa;
use App\Models\User;
use App\Support\ConfiguracaoTenant;

class AtualizarConfiguracao
{
    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, bool>
     */
    public function handle(Empresa $empresa, User $user, array $dados): array
    {
        $permitidas = $user->chavesConfigEditaveis();
        $atual = $empresa->config();

        foreach ($dados as $chave => $valor) {
            if (! in_array($chave, $permitidas, true) || ! in_array($chave, ConfiguracaoTenant::chaves(), true)) {
                continue;
            }
            $atual[$chave] = (bool) $valor;
        }

        $empresa->update(['configuracao' => $atual]);

        return $empresa->fresh()->config();
    }
}
