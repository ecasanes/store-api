<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {

            $table->increments('id');

            $table->string('name');
            $table->string('status')->default('active'); // active, disabled, deleted

            // defaults
            $table->string('default_metrics')->nullable();
            $table->time('default_start_time')->default('08:00:00');
            $table->time('default_end_time')->default('21:00:00');
            $table->string('default_color')->nullable();

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
        Schema::dropIfExists('companies');
    }
}
