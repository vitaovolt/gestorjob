<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->ehSuperAdmin() && $user->empresa_id !== null;
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $this->viewAny($user) && (int) $cliente->empresa_id === (int) $user->empresa_id;
    }

    public function create(User $user): bool
    {
        return $user->podeGerirCadastros();
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->podeGerirCadastros() && (int) $cliente->empresa_id === (int) $user->empresa_id;
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $this->update($user, $cliente);
    }
}
