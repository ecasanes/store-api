<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnDiscountToDecimalOnPriceRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('price_rules', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::table('price_rules', function (Blueprint $table) {
            $table->decimal('discount')->nullable()->after('type');
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
            $table->dropColumn('discount');
        });

        Schema::table('price_rules', function (Blueprint $table) {
            $table->string('discount')->nullable()->after('type');
        });
    }
}
