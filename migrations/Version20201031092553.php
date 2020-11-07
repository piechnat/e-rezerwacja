<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201031092553 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tag_room (tag_id INT NOT NULL, room_id INT NOT NULL, INDEX IDX_9C90EE2DBAD26311 (tag_id), INDEX IDX_9C90EE2D54177093 (room_id), PRIMARY KEY(tag_id, room_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_user (tag_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_639C69FFBAD26311 (tag_id), INDEX IDX_639C69FFA76ED395 (user_id), PRIMARY KEY(tag_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tag_room ADD CONSTRAINT FK_9C90EE2DBAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_room ADD CONSTRAINT FK_9C90EE2D54177093 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_user ADD CONSTRAINT FK_639C69FFBAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_user ADD CONSTRAINT FK_639C69FFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tag_room');
        $this->addSql('DROP TABLE tag_user');
    }
}
