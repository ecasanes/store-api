<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHasMultipleAccessColumnToBranchStaffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('branch_staffs', function (Blueprint $table) {
            $table->integer('has_multiple_access')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('branch_staffs', function (Blueprint $table) {
            $table->dropColumn('has_multiple_access');
        });
    }
}
