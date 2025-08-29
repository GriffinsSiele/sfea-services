<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230421091427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix inconsistent foreign keys for Message table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needsv
        $this->addSql(
            <<<'SQL'
update Client
set MessageId = null
where MessageId = 0
SQL,
        );

        $this->addSql(
            <<<'SQL'
update SystemUsers
set MessageId = null
where MessageId = 0
SQL,
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
