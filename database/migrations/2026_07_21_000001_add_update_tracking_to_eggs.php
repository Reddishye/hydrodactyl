<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->string('last_update_hash', 64)->nullable()->after('update_url');
            $table->string('last_etag', 255)->nullable()->after('last_update_hash');
            $table->string('last_modified', 255)->nullable()->after('last_etag');
            $table->timestamp('last_update_check_at')->nullable()->after('last_modified');
            $table->timestamp('last_update_applied_at')->nullable()->after('last_update_check_at');
            $table->boolean('exclude_from_updates')->default(false)->after('last_update_applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn([
                'last_update_hash',
                'last_etag',
                'last_modified',
                'last_update_check_at',
                'last_update_applied_at',
                'exclude_from_updates',
            ]);
        });
    }
};
