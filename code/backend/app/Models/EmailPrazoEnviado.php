<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailPrazoEnviado extends Model
{
    protected $table = 'emails_prazo_enviados';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'tarefa_id',
        'dia',
    ];

    protected function casts(): array
    {
        return [
            'dia' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }
}
