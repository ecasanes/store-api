<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteUnusedColumnsOnActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('user_firstname');
            $table->dropColumn('user_lastname');
            $table->dropColumn('user_email');
            $table->dropColumn('user_phone');
            $table->dropColumn('role_name');
            $table->dropColumn('transaction_type');
            $table->dropColumn('transaction_status');
            $table->dropColumn('transaction_type_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('user_firstname')->nullable();
            $table->string('user_lastname')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_phone')->nullable();
            $table->string('role_name')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('transaction_status')->nullable();
            $table->string('transaction_type_name')->nullable();
        });
    }
}
