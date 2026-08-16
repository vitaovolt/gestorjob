<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarefaChecklistItem extends Model
{
    protected $table = 'tarefa_checklist_itens';

    protected $fillable = [
        'tarefa_id',
        'titulo',
        'feito',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'feito' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }
}
