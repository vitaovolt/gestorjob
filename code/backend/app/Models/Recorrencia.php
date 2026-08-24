<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use App\Support\Expediente;
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
        'briefing',
        'checklist',
        'frequencia',
        'dias',
        'responsavel_id',
        'responsavel_ids',
        'horizonte_semanas',
        'ativa',
    ];

    protected function casts(): array
    {
        return [
            'horizonte_semanas' => 'integer',
            'ativa' => 'boolean',
            'dias' => 'array',
            'responsavel_ids' => 'array',
            'checklist' => 'array',
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
     * @return list<int>
     */
    public function idsResponsaveis(): array
    {
        $ids = array_values(array_filter(array_map('intval', $this->responsavel_ids ?? [])));
        if ($ids !== []) {
            return $ids;
        }

        return $this->responsavel_id ? [(int) $this->responsavel_id] : [];
    }

    /**
     * @return array{frequencia:?string,dias:list<string>}
     */
    public function template(): array
    {
        $this->loadMissing(['servico', 'empresa']);
        $freq = $this->frequencia;
        $dias = array_values(array_filter(array_map('strval', $this->dias ?? [])));

        if (! $freq) {
            $rec = is_array($this->servico?->recorrencia) ? $this->servico->recorrencia : [];
            $freq = $rec['frequencia'] ?? null;
            $dias = array_values(array_filter(array_map('strval', $rec['dias'] ?? [])));
        }

        if ($freq === 'diaria') {
            $dias = Expediente::da($this->empresa)->dias();
        }

        return [
            'frequencia' => $freq,
            'dias' => $dias,
        ];
    }
}
