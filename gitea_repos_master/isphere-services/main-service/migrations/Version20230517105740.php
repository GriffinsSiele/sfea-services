<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517105740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            <<<'SQL'
CREATE TABLE okato (
    ter VARCHAR(2) NOT NULL COMMENT 'Код региона',
    kod1 VARCHAR(3) NOT NULL COMMENT 'Код района/города',
    kod2 VARCHAR(3) NOT NULL COMMENT 'Код рабочего поселка/сельсовета',
    kod3 VARCHAR(3) NOT NULL COMMENT 'Код сельского населенного пункта',
    razdel VARCHAR(1) NOT NULL COMMENT 'Код раздела',
    name1 VARCHAR(250) NOT NULL COMMENT 'Наименование территории',
    centrum VARCHAR(80) COMMENT 'Дополнительная информация',
    nom_descr VARCHAR(8000) COMMENT 'Описание',
    nom_akt INT NOT NULL COMMENT 'Номер изменения',
    status INT NOT NULL COMMENT 'Тип изменения',
    date_utv DATETIME NOT NULL COMMENT 'Дата принятия(DC2Type:datetime_immutable)',
    date_vved DATETIME NOT NULL COMMENT 'Дата введения(DC2Type:datetime_immutable)',
    PRIMARY KEY(ter, kod1, kod2, kod3, razdel)
) DEFAULT
    CHARACTER SET utf8mb4
    COLLATE `utf8mb4_unicode_ci`
    ENGINE = InnoDB
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE okato');
    }
}
