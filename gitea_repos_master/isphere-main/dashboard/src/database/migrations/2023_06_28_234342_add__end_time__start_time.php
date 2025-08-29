<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndTimeStartTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `Client` ADD `StartTime` timestamp NULL DEFAULT NULL AFTER `Status`');
        DB::statement('ALTER TABLE `Client` ADD `EndTime` timestamp NULL DEFAULT NULL AFTER StartTime');

        DB::statement('ALTER TABLE `SystemUsers` ADD `StartTime` timestamp NULL DEFAULT NULL AFTER LastTime');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
