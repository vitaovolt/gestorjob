<?php

namespace App\Http\Requests;

use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $empresa = $this->route('empresa');

        return $empresa instanceof Empresa
            && $this->user()?->can('update', $empresa) === true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'plano' => ['required', Rule::in(Empresa::PLANOS)],
            'limite_usuarios' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(Empresa::STATUS)],
        ];
    }
}
