<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522074056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
update source
set url = 'http://сервисы.гувм.мвд.рф'
where code = 'fms';
SQL,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
update source
set url = 'http://services.fms.gov.ru'
where code = 'fms';
SQL,
        );
    }
}
