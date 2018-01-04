<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountRelatedColumnsToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('discount_value')->nullable();
            $table->string('discount_apply_to')->nullable();
            $table->integer('discount_product_variation_id')->nullable()->unsigned();
            $table->string('discount_product_name')->nullable();
            $table->decimal('discount_product_size')->nullable();
            $table->decimal('discount_product_metrics')->nullable();
            $table->decimal('discount_product_selling_price')->nullable();
            $table->decimal('discount_product_cost_price')->nullable();
            $table->decimal('discount_quantity')->nullable();
            $table->decimal('discount_amount')->nullable();
            $table->string('price_rule_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('discount_value');
            $table->dropColumn('discount_apply_to');
            $table->dropColumn('discount_product_variation_id');
            $table->dropColumn('discount_product_name');
            $table->dropColumn('discount_product_size');
            $table->dropColumn('discount_product_metrics');
            $table->dropColumn('discount_product_selling_price');
            $table->dropColumn('discount_product_cost_price');
            $table->dropColumn('discount_quantity');
            $table->dropColumn('discount_amount');
            $table->dropColumn('price_rule_name');
        });
    }
}
