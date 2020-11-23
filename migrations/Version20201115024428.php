<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201115024428 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_4DA2396995AC4C FOREIGN KEY (editor_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_4DA2396995AC4C ON reservations (editor_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_4DA2396995AC4C');
        $this->addSql('DROP INDEX IDX_4DA2396995AC4C ON reservations');
    }
}
