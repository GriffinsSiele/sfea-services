<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE Phone (
            Id INT NOT NULL AUTO_INCREMENT,
            ParentId INT NOT NULL,
            ParentType ENUM("user","client"),
            Number VARCHAR(16) NOT NULL,
            InnerCode VARCHAR(64) NOT NULL DEFAULT "",
            Notice VARCHAR(256) NOT NULL DEFAULT "",
            PRIMARY KEY(Id),
            UNIQUE KEY(ParentId, ParentType, Number)
        )
        ENGINE=INNODB;');

        DB::statement('INSERT INTO Phone (ParentId, ParentType, Number) (SELECT Id, "user", Phone FROM SystemUsers WHERE Phone IS NOT NULL AND Phone<>"")');
        DB::statement('INSERT INTO Phone (ParentId, ParentType, Number) (SELECT Id, "client", Phone FROM Client WHERE Phone IS NOT NULL AND Phone<>"")');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('Phone');
    }
}
