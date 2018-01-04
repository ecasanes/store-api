<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductVariationIdOnPriceRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('price_rules', function (Blueprint $table) {
            $table->integer('product_variation_id')->unsigned()->nullable();
            $table->foreign('product_variation_id')
                ->references('id')
                ->on('product_variations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('price_rules', function (Blueprint $table) {
            $table->dropForeign(['product_variation_id']);
            $table->dropColumn('product_variation_id');
        });
    }
}
