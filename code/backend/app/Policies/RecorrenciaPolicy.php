<?php

namespace App\Policies;

use App\Models\Recorrencia;
use App\Models\User;

class RecorrenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->podeGerirCadastros();
    }

    public function view(User $user, Recorrencia $recorrencia): bool
    {
        return $user->podeGerirCadastros()
            && (int) $user->empresa_id === (int) $recorrencia->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->podeGerirCadastros();
    }

    public function update(User $user, Recorrencia $recorrencia): bool
    {
        return $this->view($user, $recorrencia);
    }

    public function delete(User $user, Recorrencia $recorrencia): bool
    {
        return $this->view($user, $recorrencia);
    }
}
