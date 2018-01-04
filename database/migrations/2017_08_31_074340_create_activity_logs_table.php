<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('activity_log_type_id')->unsigned()->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('user_firstname')->nullable();
            $table->string('user_lastname')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_phone')->nullable();

            $table->integer('branch_id')->unsigned()->nullable();
            $table->string('role_name')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('transaction_status')->nullable();

            $table->string('status')->default('active');

            $table->foreign('activity_log_type_id')
                ->references('id')
                ->on('activity_log_types')
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
        Schema::dropIfExists('activity_logs');
    }
}
