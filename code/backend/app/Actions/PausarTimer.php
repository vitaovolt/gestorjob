<?php

namespace App\Actions;

use App\Models\Apontamento;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PausarTimer
{
    public function handle(Tarefa $tarefa, User $user): Apontamento
    {
        $aberto = Apontamento::query()
            ->where('tarefa_id', $tarefa->id)
            ->where('user_id', $user->id)
            ->whereNull('encerrado_em')
            ->first();

        if (! $aberto) {
            throw ValidationException::withMessages([
                'fase' => 'Não há timer em andamento nesta tarefa.',
            ]);
        }

        $segundos = (int) max(0, $aberto->iniciado_em->diffInSeconds(now()));
        $aberto->update([
            'encerrado_em' => now(),
            'segundos' => $segundos,
        ]);

        return $aberto->fresh();
    }
}
