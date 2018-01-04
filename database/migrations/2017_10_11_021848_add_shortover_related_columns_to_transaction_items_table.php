<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShortoverRelatedColumnsToTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->integer('shortover_product_variation_id')->nullable();
            $table->string('shortover_product_name')->nullable();
            $table->decimal('shortover_product_size')->nullable();
            $table->string('shortover_product_metrics')->nullable();
            $table->decimal('shortover_product_cost_price')->nullable();
            $table->decimal('shortover_product_selling_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('shortover_product_variation_id');
            $table->dropColumn('shortover_product_name');
            $table->dropColumn('shortover_product_size');
            $table->dropColumn('shortover_product_metrics');
            $table->dropColumn('shortover_product_cost_price');
            $table->dropColumn('shortover_product_selling_price');
        });
    }
}
