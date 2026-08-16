<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AlternarChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tarefa = $this->route('tarefa');
        abort_unless($tarefa instanceof \App\Models\Tarefa, 404);
        Gate::authorize('checklist', $tarefa);

        return true;
    }

    public function rules(): array
    {
        return [
            'feito' => ['required', 'boolean'],
        ];
    }
}
