<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientidToSourceaccess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `sourceaccess` ADD `clientid` int(11) NOT NULL AFTER userid');
        DB::statement('ALTER TABLE `sourceaccess` ADD KEY `clientid` (`clientid`)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
