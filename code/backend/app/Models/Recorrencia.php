<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recorrencia extends Model
{
    /** @use HasFactory<\Database\Factories\RecorrenciaFactory> */
    use HasFactory, PertenceAEmpresa;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'servico_id',
        'titulo',
        'responsavel_id',
        'horizonte_semanas',
        'ativa',
    ];

    protected function casts(): array
    {
        return [
            'horizonte_semanas' => 'integer',
            'ativa' => 'boolean',
        ];
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class);
    }

    /**
     * @return array{frequencia:?string,dias:list<string>,prazo_d_menos:int}
     */
    public function template(): array
    {
        $this->loadMissing('servico');
        $rec = is_array($this->servico?->recorrencia) ? $this->servico->recorrencia : [];

        return [
            'frequencia' => $rec['frequencia'] ?? null,
            'dias' => array_values(array_filter(array_map('strval', $rec['dias'] ?? []))),
            'prazo_d_menos' => (int) ($rec['prazo_d_menos'] ?? 1),
        ];
    }
}
