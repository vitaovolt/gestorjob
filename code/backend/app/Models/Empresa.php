<?php

namespace App\Models;

use App\Support\ConfiguracaoTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    /** @use HasFactory<\Database\Factories\EmpresaFactory> */
    use HasFactory;

    protected $fillable = [
        'nome',
        'plano',
        'limite_usuarios',
        'status',
        'configuracao',
        'wizard_concluido_em',
    ];

    public const PLANOS = ['starter', 'pro', 'enterprise'];

    public const STATUS = ['ativo', 'trial', 'suspenso'];

    protected function casts(): array
    {
        return [
            'limite_usuarios' => 'integer',
            'configuracao' => 'array',
            'wizard_concluido_em' => 'datetime',
        ];
    }

    public function wizardConcluido(): bool
    {
        return $this->wizard_concluido_em !== null;
    }

    public function scopeAtivas($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * @return array<string, bool>|bool|null
     */
    public function config(?string $chave = null): mixed
    {
        $merged = ConfiguracaoTenant::mesclar($this->configuracao);

        return $chave === null ? $merged : ($merged[$chave] ?? null);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function admin(): HasMany
    {
        return $this->hasMany(User::class)->where('papel', 'admin')->orderBy('id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function servicos(): HasMany
    {
        return $this->hasMany(Servico::class);
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class);
    }
}
