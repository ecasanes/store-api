<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveBranchIdForeignKeyOnCustomerUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('customer_users', function (Blueprint $table) {
            $table->integer('branch_id')->unsigned()->nullable();
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');

        });
    }
}
