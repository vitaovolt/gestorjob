<?php

namespace App\Actions;

use App\Models\Notificacao;
use App\Models\Tarefa;
use App\Models\User;

class NotificarResponsaveisTarefa
{
    public function __construct(
        private CriarNotificacaoInApp $criar,
    ) {}

    /**
     * @param  list<int>|null  $userIds  null = todos os responsáveis atuais
     */
    public function handle(
        Tarefa $tarefa,
        string $tipo,
        string $titulo,
        ?string $corpo = null,
        ?array $userIds = null,
        ?int $excetoUserId = null,
    ): int {
        $tarefa->loadMissing('responsaveis');
        $ids = $userIds ?? $tarefa->responsaveis->pluck('id')->all();
        $criadas = 0;

        foreach (array_unique(array_map('intval', $ids)) as $userId) {
            if ($excetoUserId !== null && $userId === $excetoUserId) {
                continue;
            }
            $user = User::query()->find($userId);
            if (! $user) {
                continue;
            }
            if ($this->criar->handle($user, $tipo, $titulo, $corpo, [
                'tarefa_id' => $tarefa->id,
            ])) {
                $criadas++;
            }
        }

        return $criadas;
    }

    public static function labelStatus(string $status): string
    {
        return match ($status) {
            'a_fazer' => 'A fazer',
            'execucao' => 'Em execução',
            'revisao' => 'Em revisão',
            'cliente' => 'Aguardando cliente',
            'aprovado' => 'Aprovado',
            'concluido' => 'Concluído',
            default => $status,
        };
    }
}
