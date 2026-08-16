<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apontamento extends Model
{
    /** @use HasFactory<\Database\Factories\ApontamentoFactory> */
    use HasFactory, PertenceAEmpresa;

    public const FASES = ['analise', 'producao', 'revisao', 'correcao'];

    protected $fillable = [
        'empresa_id',
        'tarefa_id',
        'user_id',
        'fase',
        'iniciado_em',
        'encerrado_em',
        'segundos',
        'custo_hora_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_em' => 'datetime',
            'encerrado_em' => 'datetime',
            'segundos' => 'integer',
            'custo_hora_snapshot' => 'decimal:2',
        ];
    }

    public function custo(): float
    {
        return round(($this->segundos / 3600) * (float) $this->custo_hora_snapshot, 2);
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
