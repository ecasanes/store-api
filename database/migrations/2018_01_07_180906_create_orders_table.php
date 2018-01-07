<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('buyer_user_id')->unsinged()->nullable();
            $table->integer('voucher_id')->unsigned()->nullable();
            $table->integer('payment_mode_id')->unsigned()->nullable();

            $table->string('voucher_code')->nullable();
            $table->string('discount_type')->nullable();
            $table->string('discount')->nullable();

            $table->string('address')->nullable();
            $table->decimal('total')->default(0);

            $table->string('status')->default('pending');

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
        Schema::dropIfExists('orders');
    }
}
