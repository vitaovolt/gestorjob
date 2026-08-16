<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('convite_token', 64)->nullable()->unique()->after('remember_token');
            $table->timestamp('convite_expira_em')->nullable()->after('convite_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['convite_token']);
            $table->dropColumn(['convite_token', 'convite_expira_em']);
        });
    }
};
