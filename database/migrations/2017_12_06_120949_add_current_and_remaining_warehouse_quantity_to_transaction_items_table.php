<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrentAndRemainingWarehouseQuantityToTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('current_warehouse_quantity')->nullable()->default(0);
            $table->decimal('remaining_warehouse_quantity')->nullable()->default(0);
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
            $table->dropColumn('current_warehouse_quantity');
            $table->dropColumn('remaining_warehouse_quantity');
        });
    }
}
