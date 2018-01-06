<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('province')->nullable();
            $table->string('key')->unique();

            $table->string('phone')->nullable();
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
        Schema::dropIfExists('stores');
    }
}
