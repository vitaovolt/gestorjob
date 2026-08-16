<?php

namespace App\Actions;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ConcluirWizardOnboarding
{
    public function handle(User $user): Empresa
    {
        if ($user->papel !== 'admin' || ! $user->empresa_id) {
            throw ValidationException::withMessages([
                'wizard' => ['Só o admin da agência conclui o onboarding.'],
            ]);
        }

        $empresa = $user->empresa()->firstOrFail();
        if ($empresa->wizard_concluido_em) {
            return $empresa;
        }

        $empresa->update(['wizard_concluido_em' => now()]);

        return $empresa->fresh();
    }
}
