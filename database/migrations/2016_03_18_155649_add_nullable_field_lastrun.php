<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddNullableFieldLastrun extends Migration
{
  /**
   * Run the migrations.
   */
  public function up()
  {
    $table = DB::getQueryGrammar()->wrapTable('tasks');

    switch (DB::getDriverName()) {
      case 'pgsql':
        // PostgreSQL-specific syntax
        DB::statement('ALTER TABLE ' . $table . ' ALTER COLUMN last_run DROP NOT NULL;');
        break;
      default: // MySQL/MariaDB
        // MySQL/MariaDB-specific syntax
        DB::statement('ALTER TABLE ' . $table . ' CHANGE `last_run` `last_run` TIMESTAMP NULL;');
        break;
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down()
  {
    $table = DB::getQueryGrammar()->wrapTable('tasks');

    switch (DB::getDriverName()) {
      case 'pgsql':
        // PostgreSQL-specific syntax
        DB::statement('ALTER TABLE ' . $table . ' ALTER COLUMN last_run SET NOT NULL;');
        break;
      default: // MySQL/MariaDB
        // MySQL/MariaDB-specific syntax
        DB::statement('ALTER TABLE ' . $table . ' CHANGE `last_run` `last_run` TIMESTAMP;');
        break;
    }
  }
}
