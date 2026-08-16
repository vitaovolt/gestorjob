<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    use PertenceAEmpresa;

    public const TIPO_TAREFA_ALOCADA = 'tarefa_alocada';

    public const TIPO_STATUS_ALTERADO = 'status_alterado';

    public const TIPO_PRAZO_HOJE = 'prazo_hoje';

    protected $table = 'notificacoes';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'tipo',
        'titulo',
        'corpo',
        'dados',
        'lida_em',
    ];

    protected function casts(): array
    {
        return [
            'dados' => 'array',
            'lida_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeNaoLidas($query)
    {
        return $query->whereNull('lida_em');
    }

    public function marcarComoLida(): void
    {
        if ($this->lida_em === null) {
            $this->update(['lida_em' => now()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function paraApi(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'corpo' => $this->corpo,
            'dados' => $this->dados ?? (object) [],
            'lida' => $this->lida_em !== null,
            'lida_em' => $this->lida_em?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
