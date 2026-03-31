<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331221906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `admin` (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, photo_profil LONGTEXT DEFAULT NULL, role VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, token VARCHAR(255) NOT NULL, token_created_at DATETIME DEFAULT NULL, prenom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E52F626DC3 FOREIGN KEY (user_annonce_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE role roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `admin`');
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E52F626DC3');
        $this->addSql('ALTER TABLE user CHANGE roles role JSON NOT NULL');
    }
}
