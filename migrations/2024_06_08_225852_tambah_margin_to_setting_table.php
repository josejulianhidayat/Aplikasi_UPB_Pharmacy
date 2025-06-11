<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TambahMarginToSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if the column does not already exist before adding it
        if (!Schema::hasColumn('setting', 'margin')) {
            Schema::table('setting', function (Blueprint $table) {
                $table->smallInteger('margin')
                      ->default(0)
                      ->after('ppn');
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
            $table->dropColumn('margin');
        });
    }
}

