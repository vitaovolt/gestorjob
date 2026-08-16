<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    /** @use HasFactory<\Database\Factories\ClienteFactory> */
    use HasFactory, PertenceAEmpresa;

    protected $fillable = [
        'empresa_id',
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'segmento',
        'status',
        'contato_nome',
        'email',
        'whatsapp',
        'inicio_parceria',
        'pasta_drive_url',
        'dia_vencimento',
        'fee_mensal',
        'tipo_faturamento',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'inicio_parceria' => 'date',
            'dia_vencimento' => 'integer',
            'fee_mensal' => 'decimal:2',
        ];
    }

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
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
