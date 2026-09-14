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
        'eh_cliente',
        'eh_fornecedor',
        'tipo_pessoa',
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'inscricao_municipal',
        'inscricao_estadual',
        'segmento',
        'status',
        'contato_nome',
        'email',
        'whatsapp',
        'telefone',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'inicio_parceria',
        'data_nascimento',
        'data_aniversario',
        'pasta_drive_url',
        'dia_vencimento',
        'fee_mensal',
        'tipo_faturamento',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'eh_cliente' => 'boolean',
            'eh_fornecedor' => 'boolean',
            'inicio_parceria' => 'date',
            'data_nascimento' => 'date',
            'data_aniversario' => 'date',
            'dia_vencimento' => 'integer',
            'fee_mensal' => 'decimal:2',
        ];
    }

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeSomenteClientes($query)
    {
        return $query->where('eh_cliente', true);
    }

    public function scopeSomenteFornecedores($query)
    {
        return $query->where('eh_fornecedor', true);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class);
    }

    public function rotuloPapel(): string
    {
        if ($this->eh_cliente && $this->eh_fornecedor) {
            return 'Cliente e fornecedor';
        }
        if ($this->eh_fornecedor) {
            return 'Fornecedor';
        }

        return 'Cliente';
    }
}
