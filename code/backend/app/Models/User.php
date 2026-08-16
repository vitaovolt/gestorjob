<?php

namespace App\Models;

use App\Support\ConfiguracaoTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const PAPEIS = [
        'super_admin',
        'admin',
        'gerente',
        'colaborador',
        'visualizador',
    ];

    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'password',
        'papel',
        'custo_hora',
        'carga_semanal_horas',
        'departamento',
        'convite_token',
        'convite_expira_em',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'convite_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'custo_hora' => 'decimal:2',
            'carga_semanal_horas' => 'integer',
            'convite_expira_em' => 'datetime',
        ];
    }

    public function convitePendente(): bool
    {
        return filled($this->convite_token);
    }

    public function ehSuperAdmin(): bool
    {
        return $this->papel === 'super_admin';
    }

    public function podeVerFinanceiro(): bool
    {
        return in_array($this->papel, ['super_admin', 'admin', 'gerente'], true);
    }

    public function podeCriarTarefas(): bool
    {
        if ($this->ehSuperAdmin()) {
            return false;
        }
        if (in_array($this->papel, ['admin', 'gerente'], true)) {
            return true;
        }
        if ($this->papel === 'colaborador') {
            return (bool) $this->empresa?->config('colaborador_cria_tarefas');
        }

        return false;
    }

    public function podeExcluirTarefas(): bool
    {
        if ($this->papel === 'admin') {
            return true;
        }
        if ($this->papel === 'gerente') {
            return (bool) $this->empresa?->config('gerente_exclui_tarefas');
        }

        return false;
    }

    public function veSoTarefasAlocadas(): bool
    {
        if (! in_array($this->papel, ['colaborador', 'visualizador'], true)) {
            return false;
        }

        return (bool) $this->empresa?->config('colaborador_so_alocadas');
    }

    public function podeCriarUsuarios(): bool
    {
        if ($this->papel === 'admin') {
            return true;
        }
        if ($this->papel === 'gerente') {
            return (bool) $this->empresa?->config('gerente_cria_usuarios');
        }

        return false;
    }

    public function podeVerConfiguracao(): bool
    {
        return in_array($this->papel, ['admin', 'gerente'], true);
    }

    public function podeConfigurarTenant(): bool
    {
        return $this->papel === 'admin';
    }

    public function podeAnexarArquivos(): bool
    {
        return ! $this->ehSuperAdmin() && $this->papel !== 'visualizador';
    }

    /** Cadastro de clientes/serviços: admin e gerente. */
    public function podeGerirCadastros(): bool
    {
        return in_array($this->papel, ['admin', 'gerente'], true);
    }

    /** Mover status, timer, checklist — não visualizador nem Super Admin. */
    public function podeOperarTarefas(): bool
    {
        return ! $this->ehSuperAdmin() && $this->papel !== 'visualizador';
    }

    public function wizardPendente(): bool
    {
        if ($this->papel !== 'admin' || ! $this->empresa_id) {
            return false;
        }

        $this->loadMissing('empresa');

        return $this->empresa !== null && ! $this->empresa->wizardConcluido();
    }

    /**
     * @return list<string>
     */
    public function chavesConfigEditaveis(): array
    {
        if ($this->papel === 'admin') {
            return ConfiguracaoTenant::chaves();
        }
        if ($this->papel === 'gerente') {
            return ConfiguracaoTenant::CHAVES_GERENTE;
        }

        return [];
    }

    /**
     * @return array<string, bool>
     */
    public function permissoesPayload(): array
    {
        return [
            'criar_tarefas' => $this->podeCriarTarefas(),
            'excluir_tarefas' => $this->podeExcluirTarefas(),
            'cadastrar_equipe' => $this->podeCriarUsuarios(),
            'gerir_cadastros' => $this->podeGerirCadastros(),
            'operar_tarefas' => $this->podeOperarTarefas(),
            'ver_config' => $this->podeVerConfiguracao(),
            'editar_config' => $this->podeConfigurarTenant(),
            'editar_config_parcial' => $this->papel === 'gerente',
            'anexar' => $this->podeAnexarArquivos(),
            'ver_financeiro' => $this->podeVerFinanceiro(),
            'comentar' => $this->podeOperarTarefas(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuthArray(): array
    {
        $this->loadMissing('empresa');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'papel' => $this->papel,
            'empresa_id' => $this->empresa_id,
            'departamento' => $this->departamento,
            'empresa' => $this->empresa ? [
                'id' => $this->empresa->id,
                'nome' => $this->empresa->nome,
            ] : null,
            'permissoes' => $this->permissoesPayload(),
            'wizard_pendente' => $this->wizardPendente(),
        ];
    }

    public function ehUnicoAdminDaEmpresa(): bool
    {
        if ($this->papel !== 'admin' || ! $this->empresa_id) {
            return false;
        }

        return ! static::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('papel', 'admin')
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tarefas(): BelongsToMany
    {
        return $this->belongsToMany(Tarefa::class, 'tarefa_responsaveis')->withTimestamps();
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class);
    }
}
