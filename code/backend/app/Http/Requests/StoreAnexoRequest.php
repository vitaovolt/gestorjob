<?php

namespace App\Http\Requests;

use App\Models\TarefaAnexo;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeAnexarArquivos() === true;
    }

    public function rules(): array
    {
        $lista = TarefaAnexo::MIMES;

        return [
            'arquivo' => [
                'required',
                'file',
                'max:'.TarefaAnexo::MAX_KB,
                'mimes:'.$lista,
                'extensions:'.$lista,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'arquivo.required' => 'Escolha um arquivo.',
            'arquivo.mimes' => 'Arquivo fora da lista permitida (PDF, JPG, PNG, WEBP, GIF, Word ou Excel).',
            'arquivo.extensions' => 'Arquivo fora da lista permitida (PDF, JPG, PNG, WEBP, GIF, Word ou Excel).',
            'arquivo.max' => 'O arquivo pode ter no máximo 10 MB.',
        ];
    }
}
