<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserUsageLimits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE UserUsageLimits (
            Id int(11) NOT NULL AUTO_INCREMENT,
            UserId int(11) NOT NULL,

            PeriodType VARCHAR(16) NOT NULL,
            
            PriceLimit int(11) NOT NULL DEFAULT 0,
            CountLimit int(11) NOT NULL DEFAULT 0,
            
            PRIMARY KEY(Id),
            UNIQUE KEY(UserId, PeriodType),
            FOREIGN KEY (UserId) REFERENCES SystemUsers (Id)
        )
        ENGINE=INNODB;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('UserUsageLimits');
    }
}
