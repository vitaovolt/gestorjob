<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('eh_cliente')->default(true)->after('empresa_id');
            $table->boolean('eh_fornecedor')->default(false)->after('eh_cliente');
            $table->string('tipo_pessoa', 2)->default('pj')->after('eh_fornecedor');
            $table->string('telefone', 20)->nullable()->after('whatsapp');
            $table->string('cep', 8)->nullable()->after('telefone');
            $table->string('logradouro')->nullable()->after('cep');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento')->nullable()->after('numero');
            $table->string('bairro')->nullable()->after('complemento');
            $table->string('cidade')->nullable()->after('bairro');
            $table->string('uf', 2)->nullable()->after('cidade');
            $table->string('inscricao_municipal', 32)->nullable()->after('cnpj');
            $table->string('inscricao_estadual', 32)->nullable()->after('inscricao_municipal');
            $table->date('data_nascimento')->nullable()->after('inicio_parceria');
            $table->date('data_aniversario')->nullable()->after('data_nascimento');

            $table->index(['empresa_id', 'eh_cliente']);
            $table->index(['empresa_id', 'eh_fornecedor']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'eh_cliente']);
            $table->dropIndex(['empresa_id', 'eh_fornecedor']);
            $table->dropColumn([
                'eh_cliente',
                'eh_fornecedor',
                'tipo_pessoa',
                'telefone',
                'cep',
                'logradouro',
                'numero',
                'complemento',
                'bairro',
                'cidade',
                'uf',
                'inscricao_municipal',
                'inscricao_estadual',
                'data_nascimento',
                'data_aniversario',
            ]);
        });
    }
};
