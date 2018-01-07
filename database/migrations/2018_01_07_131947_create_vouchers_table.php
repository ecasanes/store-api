<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {

            $table->increments('id');

            $table->string('name')->nullable();
            $table->string('code')->unique();

            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();

            $table->string('discount_type')->nullable(); // percent, fixed
            $table->decimal('discount')->default(0); // percent, fixed

            $table->string('status')->default('active'); // active, disabled, deleted

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
        Schema::dropIfExists('vouchers');
    }
}
