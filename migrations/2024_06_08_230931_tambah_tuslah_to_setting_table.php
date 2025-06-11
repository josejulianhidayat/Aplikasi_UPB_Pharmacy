<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TambahTuslahToSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if the column does not already exist before adding it
        if (!Schema::hasColumn('setting', 'tuslah')) {
            Schema::table('setting', function (Blueprint $table) {
                $table->decimal('tuslah', 5, 2)
                      ->default(0)
                      ->after('margin');
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
            $table->dropColumn('tuslah');
        });
    }
}

