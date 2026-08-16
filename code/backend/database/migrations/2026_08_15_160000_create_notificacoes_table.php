<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 48);
            $table->string('titulo');
            $table->text('corpo')->nullable();
            $table->json('dados')->nullable();
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lida_em', 'created_at']);
            $table->index(['empresa_id', 'tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};
