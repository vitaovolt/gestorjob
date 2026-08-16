<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails_prazo_enviados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->date('dia');
            $table->timestamps();

            $table->unique(['user_id', 'tarefa_id', 'dia']);
            $table->index(['empresa_id', 'dia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails_prazo_enviados');
    }
};
