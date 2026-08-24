<?php

namespace App\Http\Requests;

use App\Support\ConfiguracaoTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeVerConfiguracao() === true;
    }

    public function rules(): array
    {
        $rules = [];
        foreach (ConfiguracaoTenant::chavesBool() as $chave) {
            $rules[$chave] = ['sometimes', 'boolean'];
        }
        $rules['expediente_dias'] = ['sometimes', 'array', 'min:1'];
        $rules['expediente_dias.*'] = ['string', Rule::in(ConfiguracaoTenant::DIAS_SEMANA)];
        $rules['expediente_hora_fim'] = ['sometimes', 'string', 'regex:/^\d{2}:\d{2}$/'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $dados = [];
        foreach (ConfiguracaoTenant::chavesBool() as $chave) {
            if ($this->exists($chave)) {
                $dados[$chave] = $this->boolean($chave);
            }
        }
        if ($dados !== []) {
            $this->merge($dados);
        }
    }
}
