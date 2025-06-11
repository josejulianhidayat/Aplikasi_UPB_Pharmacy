<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TambahDiskonToSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if the column does not already exist before adding it
        if (!Schema::hasColumn('setting', 'diskon')) {
            Schema::table('setting', function (Blueprint $table) {
                $table->smallInteger('diskon')
                      ->default(0)
                      ->after('tipe_nota');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setting', function (Blueprint $table) {
            $table->dropColumn('diskon');
        });
    }
}

