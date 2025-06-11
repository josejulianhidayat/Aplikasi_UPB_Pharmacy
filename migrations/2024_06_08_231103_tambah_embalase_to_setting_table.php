<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TambahEmbalaseToSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if the column does not already exist before adding it
        if (!Schema::hasColumn('setting', 'embalase')) {
            Schema::table('setting', function (Blueprint $table) {
                $table->decimal('embalase', 5, 2)
                      ->default(0)
                      ->after('tuslah');
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
            $table->dropColumn('embalase');
        });
    }
}

