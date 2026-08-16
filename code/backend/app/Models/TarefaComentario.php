<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarefaComentario extends Model
{
    use PertenceAEmpresa;

    public const TIPO_USUARIO = 'usuario';

    public const TIPO_SISTEMA = 'sistema';

    protected $fillable = [
        'empresa_id',
        'tarefa_id',
        'user_id',
        'tipo',
        'corpo',
    ];

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

    /**
     * @return array{id:int,tipo:string,corpo:string,autor:?string,criado_em:?string}
     */
    public function paraApi(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'corpo' => $this->corpo,
            'autor' => $this->tipo === self::TIPO_SISTEMA
                ? 'Sistema'
                : ($this->user?->name),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
