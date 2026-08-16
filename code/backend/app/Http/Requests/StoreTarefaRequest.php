<?php

namespace App\Http\Requests;

use App\Models\Tarefa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTarefaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Tarefa::class) === true;
    }

    public function rules(): array
    {
        $empresaId = $this->user()?->empresa_id;

        return [
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)],
            'servico_id' => ['nullable', Rule::exists('servicos', 'id')->where('empresa_id', $empresaId)],
            'titulo' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Tarefa::STATUS)],
            'prioridade' => ['sometimes', Rule::in(Tarefa::PRIORIDADES)],
            'prazo_em' => ['nullable', 'date'],
            'briefing' => ['nullable', 'string'],
            'recorrente' => ['sometimes', 'boolean'],
            'responsavel_ids' => ['nullable', 'array'],
            'responsavel_ids.*' => ['integer', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
            'checklist' => ['nullable', 'array'],
        ];
    }
}
