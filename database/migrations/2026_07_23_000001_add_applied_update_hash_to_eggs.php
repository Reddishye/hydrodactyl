<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->string('applied_update_hash', 64)->nullable()->after('last_update_applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('applied_update_hash');
        });
    }
};
