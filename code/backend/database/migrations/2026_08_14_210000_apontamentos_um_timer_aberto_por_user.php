<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX apontamentos_um_aberto_por_user ON apontamentos (user_id) WHERE encerrado_em IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS apontamentos_um_aberto_por_user');
    }
};
