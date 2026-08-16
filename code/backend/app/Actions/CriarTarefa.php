<?php

namespace App\Actions;

use App\Models\Notificacao;
use App\Models\Servico;
use App\Models\Tarefa;

class CriarTarefa
{
    public function __construct(
        private NotificarResponsaveisTarefa $notificar,
    ) {}

    public function handle(array $dados, ?int $atorId = null): Tarefa
    {
        $servico = isset($dados['servico_id'])
            ? Servico::query()->find($dados['servico_id'])
            : null;

        $tarefa = Tarefa::query()->create([
            'cliente_id' => $dados['cliente_id'],
            'servico_id' => $dados['servico_id'] ?? null,
            'titulo' => $dados['titulo'],
            'status' => $dados['status'] ?? 'a_fazer',
            'prioridade' => $dados['prioridade'] ?? 'media',
            'prazo_em' => $dados['prazo_em'] ?? null,
            'briefing' => $dados['briefing'] ?? null,
            'recorrente' => (bool) ($dados['recorrente'] ?? false),
        ]);

        $ids = $dados['responsavel_ids'] ?? [];
        if ($ids !== []) {
            $tarefa->responsaveis()->sync($ids);
        }

        $checklist = $dados['checklist'] ?? $servico?->checklist_padrao ?? [];
        foreach (array_values($checklist) as $ordem => $item) {
            $titulo = is_array($item) ? (string) ($item['titulo'] ?? '') : (string) $item;
            if ($titulo === '') {
                continue;
            }
            $tarefa->checklistItens()->create([
                'titulo' => $titulo,
                'feito' => is_array($item) ? (bool) ($item['feito'] ?? false) : false,
                'ordem' => $ordem,
            ]);
        }

        $tarefa->load(['cliente', 'servico', 'responsaveis', 'checklistItens']);

        if ($ids !== []) {
            $this->notificar->handle(
                $tarefa,
                Notificacao::TIPO_TAREFA_ALOCADA,
                'Você foi alocado',
                $tarefa->titulo,
                array_map('intval', $ids),
                $atorId,
            );
        }

        return $tarefa;
    }
}
