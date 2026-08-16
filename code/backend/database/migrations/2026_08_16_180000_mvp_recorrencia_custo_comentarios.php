<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->string('titulo');
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('horizonte_semanas')->default(4);
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->unique(
                ['empresa_id', 'cliente_id', 'servico_id', 'titulo'],
                'recorrencias_serie_unique'
            );
        });

        Schema::table('tarefas', function (Blueprint $table) {
            $table->foreignId('recorrencia_id')->nullable()->after('servico_id')->constrained('recorrencias')->nullOnDelete();
            $table->date('ocorrencia_em')->nullable()->after('recorrencia_id');
        });

        Schema::table('tarefas', function (Blueprint $table) {
            $table->unique(['recorrencia_id', 'ocorrencia_em'], 'tarefas_recorrencia_ocorrencia_unique');
        });

        Schema::create('tarefa_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 16)->default('usuario');
            $table->text('corpo');
            $table->timestamps();

            $table->index(['tarefa_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarefa_comentarios');

        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropUnique('tarefas_recorrencia_ocorrencia_unique');
            $table->dropConstrainedForeignId('recorrencia_id');
            $table->dropColumn('ocorrencia_em');
        });

        Schema::dropIfExists('recorrencias');
    }
};
