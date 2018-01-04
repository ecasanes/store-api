<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('product_variation_id')->unsigned()->nullable();
            $table->integer('delivery_id')->unsigned()->nullable();
            $table->decimal('quantity')->default(0);

            $table->foreign('product_variation_id')
                ->references('id')
                ->on('product_variations')
                ->onDelete('set null');

            $table->foreign('delivery_id')
                ->references('id')
                ->on('deliveries')
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
        Schema::dropIfExists('delivery_items');
    }
}
