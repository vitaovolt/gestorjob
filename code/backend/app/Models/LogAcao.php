<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAcao extends Model
{
    public $timestamps = false;

    protected $table = 'logs_acoes';

    protected $fillable = [
        'user_id',
        'user_nome',
        'user_login',
        'empresa_id',
        'acao',
        'recurso_tipo',
        'recurso_id',
        'descricao',
        'payload',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
