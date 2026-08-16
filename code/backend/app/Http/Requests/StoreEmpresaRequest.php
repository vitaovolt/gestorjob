<?php

namespace App\Http\Requests;

use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Empresa::class) === true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'plano' => ['required', Rule::in(Empresa::PLANOS)],
            'limite_usuarios' => ['required', 'integer', 'min:1', 'max:500'],
            'admin_nome' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('admin_email')) {
            $this->merge(['admin_email' => strtolower(trim((string) $this->input('admin_email')))]);
        }
        if ($this->exists('nome')) {
            $this->merge(['nome' => trim((string) $this->input('nome'))]);
        }
    }
}
