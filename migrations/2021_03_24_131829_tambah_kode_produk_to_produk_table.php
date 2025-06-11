<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TambahKodeProdukToProdukTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if the column does not already exist before adding it
        if (!Schema::hasColumn('produk', 'kode_produk')) {
            Schema::table('produk', function (Blueprint $table) {
                $table->string('kode_produk')
                      ->unique()
                      ->after('id_kategori');
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
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn('kode_produk');
        });
    }
}
