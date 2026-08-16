<?php

namespace App\Actions;

use App\Models\Cliente;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExcluirCliente
{
    public function handle(Cliente $cliente): void
    {
        if ($cliente->tarefas()->exists()) {
            throw new HttpException(
                409,
                'Não dá para excluir: este cliente tem tarefas. Mova ou conclua as tarefas antes.',
            );
        }

        $cliente->delete();
    }
}
