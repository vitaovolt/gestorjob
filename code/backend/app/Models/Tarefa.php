<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    /** @use HasFactory<\Database\Factories\TarefaFactory> */
    use HasFactory, PertenceAEmpresa;

    public const STATUS = [
        'a_fazer',
        'execucao',
        'revisao',
        'cliente',
        'aprovado',
        'concluido',
    ];

    public const PRIORIDADES = ['urgente', 'alta', 'media', 'baixa'];

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'servico_id',
        'recorrencia_id',
        'ocorrencia_em',
        'titulo',
        'status',
        'prioridade',
        'prazo_em',
        'briefing',
        'fase_timer',
        'recorrente',
    ];

    protected function casts(): array
    {
        return [
            'prazo_em' => 'datetime',
            'ocorrencia_em' => 'date',
            'recorrente' => 'boolean',
        ];
    }

    public function scopeAbertas($query)
    {
        return $query->whereNotIn('status', ['aprovado', 'concluido']);
    }

    public function visivelPara(User $user): bool
    {
        if ($user->ehSuperAdmin() || (int) $this->empresa_id !== (int) $user->empresa_id) {
            return false;
        }
        if (! $user->veSoTarefasAlocadas()) {
            return true;
        }

        return $this->responsaveis()->where('users.id', $user->id)->exists();
    }

    public function scopeVisiveisPara($query, User $user)
    {
        if (! $user->veSoTarefasAlocadas()) {
            return $query;
        }

        return $query->whereHas('responsaveis', fn ($q) => $q->where('users.id', $user->id));
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

    public function responsaveis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tarefa_responsaveis')->withTimestamps();
    }

    public function checklistItens(): HasMany
    {
        return $this->hasMany(TarefaChecklistItem::class)->orderBy('ordem');
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class);
    }

    public function apontamentosAbertos(): HasMany
    {
        return $this->hasMany(Apontamento::class)->whereNull('encerrado_em');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(TarefaAnexo::class)->orderByDesc('id');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(TarefaComentario::class)->orderBy('id');
    }

    public function recorrencia(): BelongsTo
    {
        return $this->belongsTo(Recorrencia::class);
    }

    /**
     * @return array{custo:float,horas:float,segundos:int}
     */
    public function custoEHorasAcumulados(): array
    {
        $this->loadMissing('apontamentos');
        $segundos = 0;
        $custo = 0.0;

        foreach ($this->apontamentos as $apontamento) {
            if ($apontamento->encerrado_em) {
                $seg = (int) $apontamento->segundos;
            } else {
                $inicio = $apontamento->iniciado_em;
                $seg = $inicio ? max(0, (int) $inicio->diffInSeconds(now())) : 0;
            }
            $segundos += $seg;
            $custo += round(($seg / 3600) * (float) $apontamento->custo_hora_snapshot, 2);
        }

        return [
            'custo' => round($custo, 2),
            'horas' => round($segundos / 3600, 2),
            'segundos' => $segundos,
        ];
    }

    public function estaAtrasada(): bool
    {
        if (! $this->prazo_em) {
            return false;
        }

        if (in_array($this->status, ['aprovado', 'concluido'], true)) {
            return false;
        }

        return $this->prazo_em->isPast();
    }

    protected static function booted(): void
    {
        static::deleting(function (Tarefa $tarefa) {
            $tarefa->anexos()->each(fn (TarefaAnexo $anexo) => $anexo->delete());
        });
    }

    public function carregarParaApi(?int $userId = null, bool $comFinanceiro = false, bool $comComentarios = false): static
    {
        $this->loadMissing(['cliente', 'servico', 'responsaveis', 'checklistItens', 'apontamentosAbertos']);
        $this->setAttribute('atrasada', $this->estaAtrasada());

        $aberto = $this->apontamentosAbertos
            ->first(fn (Apontamento $a) => $userId === null || (int) $a->user_id === $userId);

        $this->setAttribute('timer_aberto', $aberto ? [
            'id' => $aberto->id,
            'fase' => $aberto->fase,
            'iniciado_em' => $aberto->iniciado_em?->toIso8601String(),
            'user_id' => $aberto->user_id,
        ] : null);

        $fase = $aberto?->fase ?? $this->fase_timer;
        $segundosFase = 0;
        if ($fase && $userId) {
            $segundosFase = (int) $this->apontamentos()
                ->where('user_id', $userId)
                ->where('fase', $fase)
                ->whereNotNull('encerrado_em')
                ->sum('segundos');
        }
        $this->setAttribute('segundos_fase', $segundosFase);

        if ($this->relationLoaded('anexos')) {
            $lista = $this->anexos->map->paraApi()->values()->all();
            $this->unsetRelation('anexos');
            $this->setAttribute('anexos', $lista);
        }

        if ($comComentarios) {
            $this->loadMissing(['comentarios.user']);
            $lista = $this->comentarios->map->paraApi()->values()->all();
            $this->unsetRelation('comentarios');
            $this->setAttribute('comentarios', $lista);
        }

        if ($comFinanceiro) {
            $totais = $this->custoEHorasAcumulados();
            $this->setAttribute('custo_acumulado', $totais['custo']);
            $this->setAttribute('horas_acumuladas', $totais['horas']);
        } else {
            $this->offsetUnset('custo_acumulado');
            $this->offsetUnset('horas_acumuladas');
        }

        return $this;
    }
}
