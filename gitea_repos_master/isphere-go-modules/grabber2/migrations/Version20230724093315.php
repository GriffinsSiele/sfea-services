<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230724093315 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE fns.debtam
(
    id         VARCHAR(255) NOT NULL,
    inn        VARCHAR(10)  NOT NULL,
    created_at DATE         NOT NULL,
    updated_at DATE         NOT NULL,
    PRIMARY KEY (id)
)'
        );

        $this->addSql('CREATE INDEX debtam_inn_idx ON fns.debtam (inn)');

        $this->addSql(
            // language=SQL
            'COMMENT
ON COLUMN fns.debtam.created_at IS \'(DC2Type:date_immutable)\''
        );

        $this->addSql(
            // language=SQL
            'COMMENT
ON COLUMN fns.debtam.updated_at IS \'(DC2Type:date_immutable)\''
        );

        $this->addSql(
            'CREATE TABLE fns.debtam_record
(
    id        INT            NOT NULL,
    debtam_id VARCHAR(255) DEFAULT NULL,
    name      VARCHAR(255)   NOT NULL,
    tax       NUMERIC(20, 2) NOT NULL,
    penalty   NUMERIC(20, 2) NOT NULL,
    fine      NUMERIC(20, 2) NOT NULL,
    arrears   NUMERIC(20, 2) NOT NULL,
    PRIMARY KEY (id)
)'
        );

        $this->addSql('CREATE INDEX IDX_AD2B5AC3707167DD ON fns.debtam_record (debtam_id)');

        $this->addSql(
            'ALTER TABLE fns.debtam_record
    ADD CONSTRAINT FK_AD2B5AC3707167DD FOREIGN KEY (debtam_id) REFERENCES fns.debtam (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fns.debtam_record DROP CONSTRAINT FK_AD2B5AC3707167DD');

        $this->addSql('DROP TABLE fns.debtam');

        $this->addSql('DROP TABLE fns.debtam_record');
    }
}
