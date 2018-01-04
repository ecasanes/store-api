<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBranchStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_stocks', function (Blueprint $table) {
            $table->increments('id');

            $table->decimal('quantity')->default(0);

            $table->integer('product_variation_id')->unsigned()->nullable();
            $table->integer('branch_id')->unsigned()->nullable();

            $table->foreign('product_variation_id')
                ->references('id')
                ->on('product_variations')
                ->onDelete('set null');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
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
        Schema::dropIfExists('branch_stocks');
    }
}
