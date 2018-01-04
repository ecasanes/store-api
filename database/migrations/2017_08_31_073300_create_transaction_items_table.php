<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('product_variation_id')->unsigned()->nullable();
            $table->decimal('quantity')->default(0);
            $table->string('product_name')->nullable();
            $table->decimal('product_size')->default(0);
            $table->string('product_metrics')->nullable();
            $table->decimal('product_cost_price')->default(0);
            $table->decimal('product_selling_price')->default(0);

            $table->string('status')->default('active');

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
        Schema::dropIfExists('transaction_items');
    }
}
