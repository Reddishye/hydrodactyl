<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->text('last_update_error')->nullable()->after('last_update_applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('last_update_error');
        });
    }
};
