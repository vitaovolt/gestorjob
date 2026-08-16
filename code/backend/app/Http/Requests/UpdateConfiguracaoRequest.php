<?php

namespace App\Http\Requests;

use App\Support\ConfiguracaoTenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeVerConfiguracao() === true;
    }

    public function rules(): array
    {
        $rules = [];
        foreach (ConfiguracaoTenant::chaves() as $chave) {
            $rules[$chave] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $dados = [];
        foreach (ConfiguracaoTenant::chaves() as $chave) {
            if ($this->exists($chave)) {
                $dados[$chave] = $this->boolean($chave);
            }
        }
        if ($dados !== []) {
            $this->merge($dados);
        }
    }
}
