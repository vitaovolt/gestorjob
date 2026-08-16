<?php

namespace App\Http\Requests;

use App\Models\Tarefa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTarefaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tarefa = $this->route('tarefa');
        abort_unless($tarefa instanceof Tarefa, 404);
        Gate::authorize('update', $tarefa);

        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Tarefa::STATUS)],
            'prioridade' => ['sometimes', Rule::in(Tarefa::PRIORIDADES)],
            'prazo_em' => ['nullable', 'date'],
            'briefing' => ['nullable', 'string'],
            'fase_timer' => ['nullable', 'string', 'max:32'],
        ];
    }
}
