<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

class EmpresaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ehSuperAdmin();
    }

    public function view(User $user, Empresa $empresa): bool
    {
        return $user->ehSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->ehSuperAdmin();
    }

    public function update(User $user, Empresa $empresa): bool
    {
        return $user->ehSuperAdmin();
    }
}
