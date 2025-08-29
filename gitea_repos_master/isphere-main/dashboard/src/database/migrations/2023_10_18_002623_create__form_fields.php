<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Schema::dropIfExists('RequestFormFieldRelation');
        //Schema::dropIfExists('HiddenAccessField');
        //Schema::dropIfExists('RequestFormField');
        //Schema::dropIfExists('ResponseObject');
        //Schema::dropIfExists('RequestForm');

        DB::statement('CREATE TABLE RequestForm (
            Code VARCHAR(32) NOT NULL,
            Label VARCHAR(64) NOT NULL,
            PRIMARY KEY(Code)
        )
        ENGINE=INNODB;');

        DB::statement('CREATE TABLE ResponseObject (
            Code VARCHAR(32) NOT NULL,
            Label VARCHAR(64) NOT NULL,
            PRIMARY KEY(Code)
        )
        ENGINE=INNODB;');

        DB::statement('CREATE TABLE RequestFormField (
            Code VARCHAR(32) NOT NULL,
            Name VARCHAR(64) NOT NULL,
            Label VARCHAR(64) NOT NULL,
            PRIMARY KEY(Code)
        )
        ENGINE=INNODB;');

        DB::statement('CREATE TABLE RequestFormFieldRelation (
            Id int(11) NOT NULL AUTO_INCREMENT,
            FieldCode VARCHAR(32) NOT NULL,
            ObjectCode VARCHAR(32) NOT NULL,
            FormCode VARCHAR(32) NOT NULL,
            PRIMARY KEY(Id),
            UNIQUE KEY(FieldCode, ObjectCode, FormCode),
            FOREIGN KEY (FieldCode) REFERENCES RequestFormField (Code),
            FOREIGN KEY (FormCode) REFERENCES RequestForm (Code),
            FOREIGN KEY (ObjectCode) REFERENCES ResponseObject (Code)
        )
        ENGINE=INNODB;');

        DB::statement('CREATE TABLE HiddenAccessField (
            Id int(11) NOT NULL AUTO_INCREMENT,
            AccessId int(11) NOT NULL,
            RFFRelationId int(11) NOT NULL,
            PRIMARY KEY(Id),
            UNIQUE KEY(RFFRelationId, AccessId)
        )
        ENGINE=INNODB;');

        DB::statement('INSERT INTO RequestForm (Code, Label) VALUES ("PersonForm", "Проверка физ.лица"), ("OrgForm", "Проверка организации"), ("PhoneForm", "Проверка телефона"), ("NickForm", "Псевдоним"),
            ("EmailForm", "Проверка e-mail"), ("SocProfileForm", "Проверка профиля соцсети"), ("TelegramForm", "Проверка telegram"), ("SkypeForm", "Проверка skype"), ("CarForm", "Проверка автомобиля"),
            ("IPForm", "Проверка ip-адреса")');

        DB::statement('INSERT INTO ResponseObject (Code, Label) VALUES ("PersonReq", "Физическое лицо"), ("PhoneReq", "Телефон"), ("EmailReq", "Электронная почта"), ("IPReq", "IP-адрес"), ("SkypeReq", "Skype"), ("TelegramReq", "Telegram"), ("URLReq", "URL"), ("CarReq", "Автомобиль"), ("OrgReq", "Организация"), ("NickReq", "Псевдоним")');

        $fields = [
            ['first', 'first', 'Имя', 'PersonReq', 'PersonForm'],
            ['middle', 'middle', 'Отчество', 'PersonReq', 'PersonForm'],
            ['paternal', 'paternal', 'Фамилия', 'PersonReq', 'PersonForm'],
            ['birthDt', 'birthDt', 'Дата рождения', 'PersonReq', 'PersonForm'],
            ['passportSeries', 'passport_series', 'Серия паспорта', 'PersonReq', 'PersonForm'],
            ['passportNumber', 'passport_number', 'Номер паспорта', 'PersonReq', 'PersonForm'],
            ['issueDate', 'issueDate', 'Дата выдачи паспорта', 'PersonReq', 'PersonForm'],
            ['issueAuthority', 'issueAuthority', 'Кем выдан паспорт', 'PersonReq', 'PersonForm'],
            ['inn', 'inn', 'ИНН', 'PersonReq', 'PersonForm'],
            ['snils', 'snils', 'СНИЛС', 'PersonReq', 'PersonForm'],
            ['driverNumber', 'driver_number', 'Серия и номер в/у', 'PersonReq', 'PersonForm'],
            ['driverDate', 'driver_date', 'Дата выдачи в/у', 'PersonReq', 'PersonForm'],
            ['regionId', 'region_id', 'Код региона', 'PersonReq', 'PersonForm'],

            ['phone', 'phone', 'Номер телефона', 'PhoneReq', 'PhoneForm'],

            ['email', 'email', 'Электронная почта', 'EmailReq', 'EmailForm'],

            ['ip', 'ip', 'IP-адрес', 'IPReq', 'IPForm'],

            ['skype', 'skype', 'Логин skype', 'SkypeReq', 'SkypeForm'],

            ['telegram', 'skype', 'Имя telegram', 'TelegramReq', 'TelegramForm'], // ??

            ['nick', 'nick', 'Псевдоним', 'NickReq', 'NickForm'],

            ['url', 'url', 'Псевдоним', 'URLReq', 'SocProfileForm'],

            ['vin', 'vin', 'VIN', 'CarReq', 'CarForm'],
            ['bodynum', 'bodynum', 'Номер кузова', 'CarReq', 'CarForm'],
            ['chassis', 'chassis', 'Номер шасси', 'CarReq', 'CarForm'],
            ['regnum', 'regnum', 'Гос.номер', 'CarReq', 'CarForm'],
            ['ctc', 'ctc', 'Серия и номер СТС', 'CarReq', 'CarForm'],
            ['pts', 'pts', 'Серия и номер ПТС', 'CarReq', 'CarForm'],

            ['inn_org', 'inn', 'ИНН', 'OrgReq', 'OrgForm'],
            ['ogrn', 'ogrn', 'ОГРН', 'OrgReq', 'OrgForm'],
            ['name', 'name', 'Название', 'OrgReq', 'OrgForm'],
            ['address_org', 'address', 'Адрес', 'OrgReq', 'OrgForm'],
            ['regionIdOrg', 'region_id', 'Код региона', 'OrgReq', 'OrgForm'],
            ['bikOrg', 'bik', 'БИК банка', 'OrgReq', 'OrgForm'],
        ];

        foreach ($fields as $field) {
            DB::statement("INSERT INTO RequestFormField (Code, Name, Label) VALUES ('{$field[0]}', '{$field[1]}', '{$field[2]}')");
            DB::statement("INSERT INTO RequestFormFieldRelation (FieldCode, ObjectCode, FormCode) VALUES ('{$field[0]}', '{$field[3]}', '{$field[4]}')");
        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /*
         * drop table RequestFormFieldRelation;
drop table RequestFormField;
drop table ResponseObject;
drop table RequestForm;
drop table HiddenAccessField;
         */
        //Schema::dropIfExists('_form_fileds');
    }
}
