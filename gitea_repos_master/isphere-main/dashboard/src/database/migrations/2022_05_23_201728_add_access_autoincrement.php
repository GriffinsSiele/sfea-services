<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccessAutoincrement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE Access DROP PRIMARY KEY;');
        DB::statement('ALTER TABLE `Access` add `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
        DB::statement('UPDATE Access SET Id=Level');

        $res = DB::table('Access')
            ->selectRaw('IFNULL(max(Level) + 1,1) AS last')
            ->first();
        DB::statement('ALTER TABLE `Access` AUTO_INCREMENT='.$res->last);

        //DB::statement('ALTER TABLE `Access` modify `Level` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
        DB::statement('ALTER TABLE `Access` change `Level` `Level_old` int(11) DEFAULT NULL');
        DB::statement('ALTER TABLE `Access` change `Id` `Level` int(11) NOT NULL AUTO_INCREMENT');
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
