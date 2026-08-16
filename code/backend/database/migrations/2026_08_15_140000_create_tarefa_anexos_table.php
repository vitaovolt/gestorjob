<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarefa_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome_original');
            $table->string('path');
            $table->string('mime', 128)->nullable();
            $table->unsignedInteger('tamanho_bytes')->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'tarefa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarefa_anexos');
    }
};
