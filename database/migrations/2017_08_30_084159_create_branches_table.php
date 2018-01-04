<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('province')->nullable();

            $table->string('phone')->nullable();
            $table->string('status')->default('active'); // active, disabled, deleted

            $table->integer('override_default_store_time')->default(0);
            $table->time('default_start_time')->nullable();
            $table->time('default_end_time')->nullable();

            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
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
        Schema::dropIfExists('branches');
    }
}
