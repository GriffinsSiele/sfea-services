<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientUsageLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE ClientUsageLimits (
            Id int(11) NOT NULL AUTO_INCREMENT,
            ClientId int(11) NOT NULL,

            PeriodType VARCHAR(16) NOT NULL,
            
            PriceLimit int(11) NOT NULL DEFAULT 0,
            CountLimit int(11) NOT NULL DEFAULT 0,
            
            PRIMARY KEY(Id),
            UNIQUE KEY(ClientId, PeriodType),
            FOREIGN KEY (ClientId) REFERENCES Client (id)
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
        Schema::dropIfExists('ClientUsageLimits');
    }
}
