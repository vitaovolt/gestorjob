<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaDadosCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateClienteRequest extends FormRequest
{
    use ValidaDadosCliente;

    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente instanceof \App\Models\Cliente
            && $this->user()?->can('update', $cliente) === true;
    }

    public function rules(): array
    {
        $regras = $this->regrasCliente($this->route('cliente')?->id);
        $regras['nome_fantasia'] = ['sometimes', 'required', 'string', 'max:255'];

        return $regras;
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->exists('eh_cliente') || $this->exists('eh_fornecedor')) {
            $this->validarPeloMenosUmPapel($validator);
        }
    }

    protected function prepareForValidation(): void
    {
        $this->prepararDocumentoEContato();
    }
}
