<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeGerirCadastros() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $empresaId = $this->user()?->empresa_id;

        return [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)],
            'servico_id' => ['required', 'integer', Rule::exists('servicos', 'id')->where('empresa_id', $empresaId)],
            'titulo' => ['required', 'string', 'max:180'],
            'responsavel_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('empresa_id', $empresaId)
                    ->whereIn('papel', ['admin', 'gerente', 'colaborador'])),
            ],
            'horizonte_semanas' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
