<?php

namespace App\Actions;

use App\Models\Tarefa;
use App\Models\TarefaComentario;
use App\Models\User;

class RegistrarComentarioTarefa
{
    public function handle(
        Tarefa $tarefa,
        string $corpo,
        string $tipo = TarefaComentario::TIPO_USUARIO,
        ?User $autor = null,
    ): TarefaComentario {
        $corpo = trim($corpo);
        abort_if($corpo === '', 422, 'Comentário vazio.');

        return $tarefa->comentarios()->create([
            'empresa_id' => $tarefa->empresa_id,
            'user_id' => $tipo === TarefaComentario::TIPO_SISTEMA ? null : $autor?->id,
            'tipo' => $tipo,
            'corpo' => $corpo,
        ]);
    }
}
