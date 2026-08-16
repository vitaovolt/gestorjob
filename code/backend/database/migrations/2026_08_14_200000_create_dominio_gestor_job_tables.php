<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('plano', 32)->default('starter');
            $table->unsignedInteger('limite_usuarios')->default(5);
            $table->string('status', 32)->default('ativo');
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->nullOnDelete();
            $table->string('papel', 32)->default('colaborador')->after('password');
            $table->decimal('custo_hora', 10, 2)->nullable()->after('papel');
            $table->unsignedInteger('carga_semanal_horas')->nullable()->after('custo_hora');
            $table->string('departamento', 64)->nullable()->after('carga_semanal_horas');

            $table->index(['empresa_id', 'papel']);
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome_fantasia');
            $table->string('razao_social')->nullable();
            $table->string('cnpj', 14)->nullable();
            $table->string('segmento')->nullable();
            $table->string('status', 32)->default('ativo');
            $table->string('contato_nome')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->date('inicio_parceria')->nullable();
            $table->string('pasta_drive_url')->nullable();
            $table->unsignedTinyInteger('dia_vencimento')->nullable();
            $table->decimal('fee_mensal', 12, 2)->default(0);
            $table->string('tipo_faturamento', 32)->default('mensal');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->unique(['empresa_id', 'cnpj']);
        });

        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco_venda', 12, 2)->default(0);
            $table->decimal('custo_estimado', 12, 2)->nullable();
            $table->unsignedInteger('tempo_estimado_minutos')->nullable();
            $table->json('checklist_padrao')->nullable();
            $table->json('recorrencia')->nullable();
            $table->timestamps();

            $table->index('empresa_id');
        });

        Schema::create('tarefas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('titulo');
            $table->string('status', 32)->default('a_fazer');
            $table->string('prioridade', 16)->default('media');
            $table->timestamp('prazo_em')->nullable();
            $table->text('briefing')->nullable();
            $table->string('fase_timer', 32)->nullable();
            $table->boolean('recorrente')->default(false);
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'cliente_id']);
            $table->index(['empresa_id', 'prazo_em']);
        });

        Schema::create('tarefa_responsaveis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tarefa_id', 'user_id']);
        });

        Schema::create('tarefa_checklist_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->string('titulo');
            $table->boolean('feito')->default(false);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['tarefa_id', 'ordem']);
        });

        Schema::create('apontamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('fase', 32);
            $table->timestamp('iniciado_em');
            $table->timestamp('encerrado_em')->nullable();
            $table->unsignedInteger('segundos')->default(0);
            $table->decimal('custo_hora_snapshot', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'tarefa_id']);
            $table->index(['empresa_id', 'user_id']);
            $table->index(['iniciado_em', 'encerrado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apontamentos');
        Schema::dropIfExists('tarefa_checklist_itens');
        Schema::dropIfExists('tarefa_responsaveis');
        Schema::dropIfExists('tarefas');
        Schema::dropIfExists('servicos');
        Schema::dropIfExists('clientes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropIndex(['empresa_id', 'papel']);
            $table->dropColumn(['papel', 'custo_hora', 'carga_semanal_horas', 'departamento']);
        });

        Schema::dropIfExists('empresas');
    }
};
