<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaDadosCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClienteRequest extends FormRequest
{
    use ValidaDadosCliente;

    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Cliente::class) === true;
    }

    public function rules(): array
    {
        return $this->regrasCliente();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validarPeloMenosUmPapel($validator);
    }

    protected function prepareForValidation(): void
    {
        $this->prepararDocumentoEContato();
        if (! $this->exists('eh_cliente') && ! $this->exists('eh_fornecedor')) {
            $this->merge(['eh_cliente' => true, 'eh_fornecedor' => false]);
        }
    }
}
