<?php

namespace App\Http\Requests\Concerns;

use App\Rules\CnpjAlfanumerico;
use App\Rules\CpfOuCnpj;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidaDadosCliente
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasCliente(?int $clienteId = null): array
    {
        $empresaId = $this->user()?->empresa_id;

        return [
            'eh_cliente' => ['sometimes', 'boolean'],
            'eh_fornecedor' => ['sometimes', 'boolean'],
            'tipo_pessoa' => ['sometimes', Rule::in(['pf', 'pj'])],
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'cnpj' => [
                'nullable',
                'string',
                new CpfOuCnpj,
                Rule::unique('clientes', 'cnpj')->where('empresa_id', $empresaId)->ignore($clienteId),
            ],
            'inscricao_municipal' => ['nullable', 'string', 'max:32'],
            'inscricao_estadual' => ['nullable', 'string', 'max:32'],
            'segmento' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo', 'prospect'])],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'size:8'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'size:2'],
            'inicio_parceria' => ['nullable', 'date'],
            'data_nascimento' => ['nullable', 'date'],
            'data_aniversario' => ['nullable', 'date'],
            'pasta_drive_url' => ['nullable', 'string', 'max:2048'],
            'dia_vencimento' => ['nullable', 'integer', 'min:1', 'max:28'],
            'fee_mensal' => ['nullable', 'numeric', 'min:0'],
            'tipo_faturamento' => ['sometimes', Rule::in(['mensal', 'projeto', 'hora'])],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    protected function prepararDocumentoEContato(): void
    {
        if ($this->exists('cnpj')) {
            $cnpj = CnpjAlfanumerico::normalizar((string) $this->input('cnpj'));
            $this->merge(['cnpj' => $cnpj === '' ? null : $cnpj]);
        }
        foreach (['whatsapp', 'telefone', 'cep'] as $campo) {
            if ($this->exists($campo)) {
                $digits = preg_replace('/\D+/', '', (string) $this->input($campo)) ?: null;
                $this->merge([$campo => $digits]);
            }
        }
        if ($this->exists('email')) {
            $email = strtolower(trim((string) $this->input('email')));
            $this->merge(['email' => $email === '' ? null : $email]);
        }
        if ($this->exists('uf')) {
            $uf = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $this->input('uf')) ?? '');
            $this->merge(['uf' => $uf === '' ? null : substr($uf, 0, 2)]);
        }
        foreach (['eh_cliente', 'eh_fornecedor'] as $flag) {
            if ($this->exists($flag)) {
                $this->merge([$flag => filter_var($this->input($flag), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
        if ($this->exists('fee_mensal') && $this->input('fee_mensal') === null) {
            $this->merge(['fee_mensal' => 0]);
        }
    }

    protected function validarPeloMenosUmPapel(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $atual = $this->route('cliente');
            $ehCliente = $this->exists('eh_cliente')
                ? $this->boolean('eh_cliente')
                : ($atual instanceof \App\Models\Cliente ? (bool) $atual->eh_cliente : true);
            $ehFornecedor = $this->exists('eh_fornecedor')
                ? $this->boolean('eh_fornecedor')
                : ($atual instanceof \App\Models\Cliente ? (bool) $atual->eh_fornecedor : false);

            if (! $ehCliente && ! $ehFornecedor) {
                $v->errors()->add('eh_cliente', 'Marque cliente, fornecedor ou os dois.');
            }
        });
    }
}
