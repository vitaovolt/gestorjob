<?php

use App\Models\Recorrencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recorrencias', function (Blueprint $table) {
            $table->string('frequencia', 32)->nullable()->after('titulo');
            $table->json('dias')->nullable()->after('frequencia');
            $table->json('responsavel_ids')->nullable()->after('responsavel_id');
            $table->text('briefing')->nullable()->after('titulo');
            $table->json('checklist')->nullable()->after('briefing');
        });

        Schema::table('tarefas', function (Blueprint $table) {
            $table->date('inicio_em')->nullable()->after('ocorrencia_em');
        });

        Recorrencia::query()->with('servico')->each(function (Recorrencia $serie) {
            $rec = is_array($serie->servico?->recorrencia) ? $serie->servico->recorrencia : [];
            if ($rec === []) {
                return;
            }
            $serie->update([
                'frequencia' => $rec['frequencia'] ?? $serie->frequencia,
                'dias' => $rec['dias'] ?? $serie->dias,
            ]);
        });

        Schema::table('recorrencias', function (Blueprint $table) {
            $table->dropUnique('recorrencias_serie_unique');
        });

        Schema::table('recorrencias', function (Blueprint $table) {
            $table->dropForeign(['servico_id']);
        });

        Schema::table('recorrencias', function (Blueprint $table) {
            $table->unsignedBigInteger('servico_id')->nullable()->change();
            $table->foreign('servico_id')->references('id')->on('servicos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recorrencias', function (Blueprint $table) {
            $table->dropForeign(['servico_id']);
        });

        Recorrencia::query()->whereNull('servico_id')->delete();

        Schema::table('recorrencias', function (Blueprint $table) {
            $table->unsignedBigInteger('servico_id')->nullable(false)->change();
            $table->foreign('servico_id')->references('id')->on('servicos')->cascadeOnDelete();
            $table->unique(
                ['empresa_id', 'cliente_id', 'servico_id', 'titulo'],
                'recorrencias_serie_unique'
            );
            $table->dropColumn(['frequencia', 'dias', 'responsavel_ids', 'briefing', 'checklist']);
        });

        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropColumn('inicio_em');
        });
    }
};
