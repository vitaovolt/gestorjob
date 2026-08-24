<?php

namespace App\Http\Requests;

use App\Models\Tarefa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $empresaId = $this->user()?->empresa_id;

        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Tarefa::STATUS)],
            'prioridade' => ['sometimes', Rule::in(Tarefa::PRIORIDADES)],
            'prazo_em' => ['nullable', 'date'],
            'inicio_em' => ['nullable', 'date'],
            'briefing' => ['nullable', 'string'],
            'fase_timer' => ['nullable', 'string', 'max:32'],
            'responsavel_ids' => ['sometimes', 'nullable', 'array'],
            'responsavel_ids.*' => ['integer', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->exists('responsavel_ids')) {
                return;
            }
            if (! $this->user()?->podeAlocarResponsaveis()) {
                $v->errors()->add('responsavel_ids', 'Você não pode alocar responsáveis.');
            }
        });
    }
}
