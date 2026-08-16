<?php

namespace App\Policies;

use App\Models\Servico;
use App\Models\User;

class ServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->ehSuperAdmin() && $user->empresa_id !== null;
    }

    public function view(User $user, Servico $servico): bool
    {
        return $this->viewAny($user) && (int) $servico->empresa_id === (int) $user->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->podeGerirCadastros();
    }

    public function update(User $user, Servico $servico): bool
    {
        return $user->podeGerirCadastros() && (int) $servico->empresa_id === (int) $user->empresa_id;
    }

    public function delete(User $user, Servico $servico): bool
    {
        return $this->update($user, $servico);
    }
}
