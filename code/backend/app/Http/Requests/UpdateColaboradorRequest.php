<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColaboradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeCriarUsuarios() === true;
    }

    public function rules(): array
    {
        $papeis = array_values(array_filter(User::PAPEIS, fn ($p) => $p !== 'super_admin'));
        $colaboradorId = (int) $this->route('colaborador');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($colaboradorId)],
            'papel' => ['sometimes', Rule::in($papeis)],
            'custo_hora' => ['nullable', 'numeric', 'min:0'],
            'carga_semanal_horas' => ['nullable', 'integer', 'min:1', 'max:80'],
            'departamento' => ['nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }
}
