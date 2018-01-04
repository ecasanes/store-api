<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');

            $table->string('or_no');
            $table->integer('transaction_type_id')->unsigned()->nullable();
            $table->integer('staff_id')->unsigned()->nullable();
            $table->integer('customer_user_id')->unsigned()->nullable();

            $table->string('customer_firstname')->nullable();
            $table->string('customer_lastname')->nullable();
            $table->string('customer_phone')->nullable();

            $table->string('staff_firstname')->nullable();
            $table->string('staff_lastname')->nullable();
            $table->string('staff_phone')->nullable();

            $table->integer('price_rule_id')->nullable();

            $table->string('status')->nullable(); // VOID, ETC

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
        Schema::dropIfExists('transactions');
    }
}
