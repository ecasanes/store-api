<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePriceRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_rules', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name')->unique();
            $table->string('code')->unique();

            $table->string('type');
            $table->string('discount');
            $table->string('status')->default('active'); // active, deleted, disabled
            $table->string('apply_to')->default('all'); // all, members_only, etc

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
        Schema::dropIfExists('price_rules');
    }
}
