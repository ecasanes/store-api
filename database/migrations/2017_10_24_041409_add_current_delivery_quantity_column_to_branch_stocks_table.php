<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrentDeliveryQuantityColumnToBranchStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('branch_stocks', function (Blueprint $table) {
            $table->decimal('current_delivery_quantity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('branch_stocks', function (Blueprint $table) {
            $table->dropColumn('current_delivery_quantity');
        });
    }
}
