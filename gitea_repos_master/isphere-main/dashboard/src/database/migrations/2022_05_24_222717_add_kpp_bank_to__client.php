<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKppBankToClient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `Client` ADD `KPP` int(11) DEFAULT NULL AFTER OGRN');
        DB::statement('ALTER TABLE `Client` ADD `Bank` varchar(128) DEFAULT NULL AFTER BIK');
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
