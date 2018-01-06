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

            $table->string('or_no')->nullable();
            $table->integer('transaction_type_id')->unsigned()->nullable();

            $table->integer('customer_user_id')->unsigned()->nullable();
            $table->string('customer_firstname')->nullable();
            $table->string('customer_lastname')->nullable();
            $table->string('customer_phone')->nullable();

            $table->integer('user_id')->unsinged()->nullable();

            $table->integer('store_id')->unsigned()->nullable();

            $table->decimal('discount')->default(0);
            $table->text('remarks')->nullable();

            $table->decimal('grand_total')->default(0);

            $table->string('status')->nullable()->default('active'); // VOID, ETC

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
