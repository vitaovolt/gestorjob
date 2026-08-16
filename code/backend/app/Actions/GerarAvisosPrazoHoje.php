<?php

namespace App\Actions;

use App\Models\Notificacao;
use App\Models\Tarefa;
use Illuminate\Support\Carbon;

class GerarAvisosPrazoHoje
{
    public function __construct(
        private NotificarResponsaveisTarefa $notificar,
        private EnviarEmailPrazoHoje $enviarEmail,
    ) {}

    /**
     * @return array{in_app: int, emails: int}
     */
    public function handle(?Carbon $dia = null): array
    {
        $dia ??= now();
        $inicio = $dia->copy()->startOfDay();
        $fim = $dia->copy()->endOfDay();
        $inApp = 0;
        $emails = 0;

        $tarefas = Tarefa::query()
            ->withoutGlobalScopes()
            ->with('responsaveis')
            ->whereNotNull('prazo_em')
            ->whereBetween('prazo_em', [$inicio, $fim])
            ->whereNotIn('status', ['concluido', 'aprovado'])
            ->get();

        foreach ($tarefas as $tarefa) {
            foreach ($tarefa->responsaveis as $user) {
                $jaInApp = Notificacao::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $user->id)
                    ->where('tipo', Notificacao::TIPO_PRAZO_HOJE)
                    ->whereDate('created_at', $inicio->toDateString())
                    ->where('dados->tarefa_id', $tarefa->id)
                    ->exists();

                if (! $jaInApp) {
                    $n = $this->notificar->handle(
                        $tarefa,
                        Notificacao::TIPO_PRAZO_HOJE,
                        'Prazo hoje',
                        $tarefa->titulo.' · '.$inicio->format('d/m'),
                        [$user->id],
                    );
                    $inApp += $n;
                }

                if ($this->enviarEmail->handle($user, $tarefa, $inicio)) {
                    $emails++;
                }
            }
        }

        return ['in_app' => $inApp, 'emails' => $emails];
    }
}
