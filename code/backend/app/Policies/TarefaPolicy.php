<?php

namespace App\Policies;

use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TarefaPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->ehSuperAdmin() && $user->empresa_id !== null;
    }

    public function view(User $user, Tarefa $tarefa): Response
    {
        if (! $this->viewAny($user) || ! $tarefa->visivelPara($user)) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function create(User $user): bool
    {
        return $user->podeCriarTarefas();
    }

    public function update(User $user, Tarefa $tarefa): Response
    {
        if (! $tarefa->visivelPara($user)) {
            return Response::denyAsNotFound();
        }

        return $user->podeOperarTarefas()
            ? Response::allow()
            : Response::deny();
    }

    public function delete(User $user, Tarefa $tarefa): Response
    {
        if (! $tarefa->visivelPara($user)) {
            return Response::denyAsNotFound();
        }

        return $user->podeExcluirTarefas()
            ? Response::allow()
            : Response::deny();
    }

    public function timer(User $user, Tarefa $tarefa): Response
    {
        return $this->update($user, $tarefa);
    }

    public function checklist(User $user, Tarefa $tarefa): Response
    {
        return $this->update($user, $tarefa);
    }
}
