<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_acoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome')->nullable();
            $table->string('user_login')->nullable();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('acao', 64);
            $table->string('recurso_tipo', 64)->nullable();
            $table->unsignedBigInteger('recurso_id')->nullable();
            $table->string('descricao');
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('acao');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_acoes');
    }
};
