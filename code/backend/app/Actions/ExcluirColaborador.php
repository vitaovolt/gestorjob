<?php

namespace App\Actions;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExcluirColaborador
{
    public function handle(User $ator, User $colaborador): void
    {
        if ((int) $colaborador->empresa_id !== (int) $ator->empresa_id) {
            throw new HttpException(404, 'Não encontrado.');
        }

        if ((int) $colaborador->id === (int) $ator->id) {
            throw new HttpException(409, 'Não dá para excluir a própria conta.');
        }

        if ($colaborador->ehUnicoAdminDaEmpresa()) {
            throw new HttpException(409, 'Não dá para excluir o único admin da agência.');
        }

        if ($colaborador->tarefas()->exists() || $colaborador->apontamentos()->exists()) {
            throw new HttpException(
                409,
                'Não dá para excluir: esta pessoa tem tarefas ou horas apontadas.',
            );
        }

        $colaborador->delete();
    }
}
