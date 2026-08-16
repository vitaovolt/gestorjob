<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TarefaAnexo extends Model
{
    use PertenceAEmpresa;

    public const DISCO = 'anexos';

    public const MAX_POR_TAREFA = 20;

    public const MAX_KB = 10240;

    /** Extensões permitidas (allowlist). Qualquer outra é rejeitada. */
    public const MIMES = 'pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx';

    /**
     * @return list<string>
     */
    public static function extensoesPermitidas(): array
    {
        return explode(',', self::MIMES);
    }

    protected $fillable = [
        'empresa_id',
        'tarefa_id',
        'user_id',
        'nome_original',
        'path',
        'mime',
        'tamanho_bytes',
    ];

    protected $hidden = [
        'path',
    ];

    protected function casts(): array
    {
        return [
            'tamanho_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (TarefaAnexo $anexo) {
            if (filled($anexo->path)) {
                Storage::disk(self::DISCO)->delete($anexo->path);
            }
        });
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

    /**
     * @return array<string, mixed>
     */
    public function paraApi(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome_original,
            'mime' => $this->mime,
            'tamanho_bytes' => $this->tamanho_bytes,
            'user_id' => $this->user_id,
            'autor' => $this->user?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
