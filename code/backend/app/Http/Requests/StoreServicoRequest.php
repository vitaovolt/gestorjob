<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $servico = $this->route('servico');
        if ($servico instanceof \App\Models\Servico) {
            return $this->user()?->can('update', $servico) === true;
        }

        return $this->user()?->can('create', \App\Models\Servico::class) === true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'preco_venda' => ['nullable', 'numeric', 'min:0'],
            'custo_estimado' => ['nullable', 'numeric', 'min:0'],
            'tempo_estimado_minutos' => ['nullable', 'integer', 'min:0'],
            'checklist_padrao' => ['nullable', 'array'],
            'checklist_padrao.*' => ['string', 'max:255'],
            'recorrencia' => ['nullable', 'array'],
            'recorrencia.frequencia' => ['nullable', 'string', 'max:32'],
            'recorrencia.dias' => ['nullable', 'array'],
            'recorrencia.prazo_d_menos' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
