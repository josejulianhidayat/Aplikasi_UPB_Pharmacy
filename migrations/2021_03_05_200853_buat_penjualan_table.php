<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BuatPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->increments('id_penjualan');
            $table->string('kode_produk')->nullable();
            $table->string('nama_produk')->nullable();
            $table->integer('total_item');
            $table->decimal('total_harga',10,2);
            $table->tinyInteger('diskon')->default(0);
            $table->decimal('bayar',10,2)->default(0);
            $table->decimal('diterima',10,2)->default(0);
            $table->integer('id_user');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penjualan');
    }
}
