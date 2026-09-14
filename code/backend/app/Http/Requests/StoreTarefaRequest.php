<?php

namespace App\Http\Requests;

use App\Models\Tarefa;
use App\Support\ConfiguracaoTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTarefaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tarefa::class) === true;
    }

    public function rules(): array
    {
        $empresaId = $this->user()?->empresa_id;

        return [
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)->where('eh_cliente', true),
            ],
            'servico_id' => ['nullable', Rule::exists('servicos', 'id')->where('empresa_id', $empresaId)],
            'titulo' => ['required', 'string', 'max:255'],
            'prioridade' => ['sometimes', Rule::in(Tarefa::PRIORIDADES)],
            'prazo_em' => ['nullable', 'date'],
            'inicio_em' => ['nullable', 'date'],
            'briefing' => ['nullable', 'string'],
            'recorrente' => ['sometimes', 'boolean'],
            'responsavel_ids' => ['nullable', 'array'],
            'responsavel_ids.*' => ['integer', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['string', 'max:255'],
            'repeticao' => ['nullable', 'array'],
            'repeticao.frequencia' => ['nullable', 'string', Rule::in(['nunca', 'diaria', 'semanal'])],
            'repeticao.dias' => ['nullable', 'array'],
            'repeticao.dias.*' => ['string', Rule::in(ConfiguracaoTenant::DIAS_SEMANA)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $ids = $this->input('responsavel_ids', []);
            if ($ids !== [] && $ids !== null && ! $this->user()?->podeAlocarResponsaveis()) {
                $v->errors()->add('responsavel_ids', 'Você não pode alocar responsáveis.');
            }
            $freq = $this->input('repeticao.frequencia');
            $dias = $this->input('repeticao.dias', []);
            if ($freq === 'semanal' && (! is_array($dias) || $dias === [])) {
                $v->errors()->add('repeticao.dias', 'Informe os dias da semana.');
            }
        });
    }
}
