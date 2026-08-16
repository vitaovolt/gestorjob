<?php

namespace App\Actions;

use App\Models\Apontamento;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IniciarTimer
{
    public function __construct(private PausarTimer $pausarTimer) {}

    public function handle(Tarefa $tarefa, User $user, string $fase): Apontamento
    {
        if (! in_array($fase, Apontamento::FASES, true)) {
            throw ValidationException::withMessages([
                'fase' => 'Fase do timer inválida.',
            ]);
        }

        $aberto = Apontamento::query()
            ->where('user_id', $user->id)
            ->whereNull('encerrado_em')
            ->first();

        if ($aberto && (int) $aberto->tarefa_id !== (int) $tarefa->id) {
            throw ValidationException::withMessages([
                'fase' => 'Já existe um timer em andamento em outra tarefa. Pause antes de iniciar outro.',
            ]);
        }

        if ($aberto && $aberto->fase === $fase) {
            return $aberto;
        }

        if ($aberto) {
            $this->pausarTimer->handle($tarefa, $user);
        }

        $apontamento = Apontamento::query()->create([
            'empresa_id' => $tarefa->empresa_id,
            'tarefa_id' => $tarefa->id,
            'user_id' => $user->id,
            'fase' => $fase,
            'iniciado_em' => now(),
            'encerrado_em' => null,
            'segundos' => 0,
            'custo_hora_snapshot' => $user->custo_hora ?? 0,
        ]);

        $dados = ['fase_timer' => $fase];
        if ($tarefa->status === 'a_fazer') {
            $dados['status'] = 'execucao';
        }
        $tarefa->update($dados);

        return $apontamento;
    }
}
