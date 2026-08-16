<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servico extends Model
{
    /** @use HasFactory<\Database\Factories\ServicoFactory> */
    use HasFactory, PertenceAEmpresa;

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'preco_venda',
        'custo_estimado',
        'tempo_estimado_minutos',
        'checklist_padrao',
        'recorrencia',
    ];

    protected function casts(): array
    {
        return [
            'preco_venda' => 'decimal:2',
            'custo_estimado' => 'decimal:2',
            'tempo_estimado_minutos' => 'integer',
            'checklist_padrao' => 'array',
            'recorrencia' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class);
    }
}
