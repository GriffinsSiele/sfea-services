<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultRequestTimeout extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `SystemUsers` ADD `DefaultRequestTimeout` int(11) DEFAULT NULL AFTER DefaultPrice');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `SystemUsers` DROP `DefaultRequestTimeout`');
    }
}
