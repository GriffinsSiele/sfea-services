<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230502121032 extends AbstractMigration
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
create table RefreshToken (
    id int auto_increment not null primary key,
    refresh_token varchar(128) not null,
    username varchar(128) not null,
    valid date not null
) default character set utf8mb4
    collate `utf8mb4_unicode_ci`
    engine = InnoDB
SQL,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE RefreshToken');
    }
}
