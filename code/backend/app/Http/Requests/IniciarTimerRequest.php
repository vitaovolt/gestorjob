<?php

namespace App\Http\Requests;

use App\Models\Apontamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IniciarTimerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tarefa = $this->route('tarefa');
        abort_unless($tarefa instanceof \App\Models\Tarefa, 404);
        Gate::authorize('timer', $tarefa);

        return true;
    }

    public function rules(): array
    {
        return [
            'fase' => ['required', Rule::in(Apontamento::FASES)],
        ];
    }
}
