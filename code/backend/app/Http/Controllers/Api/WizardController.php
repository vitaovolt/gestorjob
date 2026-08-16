<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConcluirWizardOnboarding;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WizardController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('empresa');
        $pendente = $user->wizardPendente();

        return $this->ok([
            'pendente' => $pendente,
            'passos' => [
                ['id' => 1, 'titulo' => 'Serviços', 'dica' => 'Cadastre os serviços que a agência vende.'],
                ['id' => 2, 'titulo' => 'Equipe', 'dica' => 'Convide colaboradores e defina custo/hora.'],
                ['id' => 3, 'titulo' => 'Clientes', 'dica' => 'Inclua clientes com fee mensal.'],
                ['id' => 4, 'titulo' => 'Feriados', 'dica' => 'Calendário institucional fica para a fase 2 — pode pular.'],
                ['id' => 5, 'titulo' => 'Permissões', 'dica' => 'A matriz padrão já está aplicada. Confirme e abra o Kanban.'],
            ],
            'concluido_em' => $user->empresa?->wizard_concluido_em?->toIso8601String(),
        ], $pendente ? 'Wizard pendente' : 'Wizard concluído');
    }

    public function concluir(Request $request, ConcluirWizardOnboarding $concluir): JsonResponse
    {
        $concluir->handle($request->user());
        $user = $request->user()->fresh()->loadMissing('empresa');

        return $this->ok([
            'user' => $user->toAuthArray(),
        ], 'Onboarding concluído');
    }
}
