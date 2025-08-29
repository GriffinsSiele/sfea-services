<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230523122808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE bulk_request_new (bulk_id INT NOT NULL, request_new_id INT NOT NULL, INDEX IDX_AB328DF7F34FE8AC (bulk_id), INDEX IDX_AB328DF7F65F7048 (request_new_id), PRIMARY KEY(bulk_id, request_new_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE bulk_request_new ADD CONSTRAINT FK_AB328DF7F34FE8AC FOREIGN KEY (bulk_id) REFERENCES Bulk (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bulk_request_new ADD CONSTRAINT FK_AB328DF7F65F7048 FOREIGN KEY (request_new_id) REFERENCES RequestNew (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulk_request_new DROP FOREIGN KEY FK_AB328DF7F34FE8AC');
        $this->addSql('ALTER TABLE bulk_request_new DROP FOREIGN KEY FK_AB328DF7F65F7048');
        $this->addSql('DROP TABLE bulk_request_new');
    }
}
