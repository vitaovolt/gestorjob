<?php

namespace App\Actions;

use App\Models\Servico;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExcluirServico
{
    public function handle(Servico $servico): void
    {
        if ($servico->tarefas()->exists()) {
            throw new HttpException(
                409,
                'Não dá para excluir: este serviço tem tarefas. Mova ou conclua as tarefas antes.',
            );
        }

        $servico->delete();
    }
}
