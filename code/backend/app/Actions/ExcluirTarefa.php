<?php

namespace App\Actions;

use App\Models\Tarefa;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExcluirTarefa
{
    public function handle(Tarefa $tarefa): void
    {
        if ($tarefa->apontamentos()->exists()) {
            throw new HttpException(
                409,
                'Não dá para excluir: esta tarefa tem horas apontadas. Mantenha o histórico.',
            );
        }

        $tarefa->delete();
    }
}
