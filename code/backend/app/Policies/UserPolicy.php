<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->podeCriarUsuarios() || in_array($actor->papel, ['admin', 'gerente', 'colaborador', 'visualizador'], true);
    }

    public function view(User $actor, User $colaborador): bool
    {
        if ($actor->ehSuperAdmin() || ! $actor->empresa_id) {
            return false;
        }

        return (int) $colaborador->empresa_id === (int) $actor->empresa_id;
    }

    public function create(User $actor): bool
    {
        return $actor->podeCriarUsuarios();
    }

    public function update(User $actor, User $colaborador): bool
    {
        return $actor->podeCriarUsuarios()
            && (int) $colaborador->empresa_id === (int) $actor->empresa_id;
    }

    public function delete(User $actor, User $colaborador): bool
    {
        return $this->update($actor, $colaborador);
    }
}
