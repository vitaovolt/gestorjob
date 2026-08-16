<?php

namespace App\Actions;

use App\Models\TarefaAnexo;

class ExcluirAnexo
{
    public function handle(TarefaAnexo $anexo): void
    {
        $anexo->delete();
    }
}
