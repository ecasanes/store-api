<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_stocks', function (Blueprint $table) {
            $table->increments('id');

            $table->decimal('quantity')->default(0);

            $table->integer('product_variation_id')->unsigned()->nullable();
            $table->integer('store_id')->unsigned()->nullable();

            $table->foreign('product_variation_id')
                ->references('id')
                ->on('product_variations')
                ->onDelete('set null');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('set null');

            $table->softDeletes();
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
        Schema::dropIfExists('store_stocks');
    }
}
