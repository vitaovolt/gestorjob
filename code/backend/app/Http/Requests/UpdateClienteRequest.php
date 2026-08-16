<?php

namespace App\Http\Requests;

use App\Rules\CnpjAlfanumerico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente instanceof \App\Models\Cliente
            && $this->user()?->can('update', $cliente) === true;
    }

    public function rules(): array
    {
        $empresaId = $this->user()?->empresa_id;
        $clienteId = $this->route('cliente')?->id;

        return [
            'nome_fantasia' => ['sometimes', 'required', 'string', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'cnpj' => [
                'nullable',
                'string',
                new CnpjAlfanumerico,
                Rule::unique('clientes', 'cnpj')->where('empresa_id', $empresaId)->ignore($clienteId),
            ],
            'segmento' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo', 'prospect'])],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'inicio_parceria' => ['nullable', 'date'],
            'pasta_drive_url' => ['nullable', 'string', 'max:2048'],
            'dia_vencimento' => ['nullable', 'integer', 'min:1', 'max:28'],
            'fee_mensal' => ['nullable', 'numeric', 'min:0'],
            'tipo_faturamento' => ['sometimes', Rule::in(['mensal', 'projeto', 'hora'])],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('cnpj')) {
            $cnpj = CnpjAlfanumerico::normalizar((string) $this->input('cnpj'));
            $this->merge(['cnpj' => $cnpj === '' ? null : $cnpj]);
        }
        if ($this->exists('whatsapp')) {
            $digits = preg_replace('/\D+/', '', (string) $this->input('whatsapp')) ?: null;
            $this->merge(['whatsapp' => $digits]);
        }
        if ($this->exists('email')) {
            $email = strtolower(trim((string) $this->input('email')));
            $this->merge(['email' => $email === '' ? null : $email]);
        }
    }
}
