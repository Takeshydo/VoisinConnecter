<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331161253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce ADD user_annonce_id INT DEFAULT NULL, DROP username');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E52F626DC3 FOREIGN KEY (user_annonce_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F65593E52F626DC3 ON annonce (user_annonce_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E52F626DC3');
        $this->addSql('DROP INDEX IDX_F65593E52F626DC3 ON annonce');
        $this->addSql('ALTER TABLE annonce ADD username VARCHAR(255) NOT NULL, DROP user_annonce_id');
    }
}
