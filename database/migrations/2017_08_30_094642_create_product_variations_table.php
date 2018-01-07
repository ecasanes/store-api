<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->increments('id');

            $table->string('variation')->nullable();
            $table->decimal('size')->nullable();
            $table->string('metrics')->nullable();

            $table->decimal('cost_price')->default(0)->nullable();
            $table->decimal('selling_price')->default(0);
            $table->decimal('shipping_price')->default(0);

            $table->string('status')->default('active');

            $table->integer('product_id')->unsigned()->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
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
        Schema::dropIfExists('product_variations');
    }
}
