<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsTransactionColumnOnActivityLogTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_log_types', function(Blueprint $table) {

            $table->tinyInteger('is_transaction')->nullable()->unsigned()->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_log_types', function(Blueprint $table) {

            $table->dropColumn('is_transaction');

        });
    }
}
