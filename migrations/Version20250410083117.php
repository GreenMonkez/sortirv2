<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410083117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE etat (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lieu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, city VARCHAR(255) NOT NULL, postale_code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE motif_annulation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, commentaire VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, sortie_id INT NOT NULL, notified_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_ED15DF2A76ED395 (user_id), INDEX IDX_ED15DF2CC72D953 (sortie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sortie (id INT AUTO_INCREMENT NOT NULL, status_id INT NOT NULL, lieu_id INT NOT NULL, site_id INT NOT NULL, planner_id INT NOT NULL, motifs_cancel_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, start_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', duration SMALLINT NOT NULL, limit_sortie_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', limit_members INT NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', modified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', register_start_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_archive TINYINT(1) NOT NULL, INDEX IDX_3C3FD3F26BF700BD (status_id), INDEX IDX_3C3FD3F26AB213CC (lieu_id), INDEX IDX_3C3FD3F2F6BD1646 (site_id), INDEX IDX_3C3FD3F25346EAE1 (planner_id), INDEX IDX_3C3FD3F2CD77356E (motifs_cancel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, site_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, phone_number VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, pseudo VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64986CC499D (pseudo), INDEX IDX_8D93D649F6BD1646 (site_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_sortie (user_id INT NOT NULL, sortie_id INT NOT NULL, INDEX IDX_596DC8CFA76ED395 (user_id), INDEX IDX_596DC8CFCC72D953 (sortie_id), PRIMARY KEY(user_id, sortie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_log ADD CONSTRAINT FK_ED15DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_log ADD CONSTRAINT FK_ED15DF2CC72D953 FOREIGN KEY (sortie_id) REFERENCES sortie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F26BF700BD FOREIGN KEY (status_id) REFERENCES etat (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F26AB213CC FOREIGN KEY (lieu_id) REFERENCES lieu (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F25346EAE1 FOREIGN KEY (planner_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2CD77356E FOREIGN KEY (motifs_cancel_id) REFERENCES motif_annulation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_sortie ADD CONSTRAINT FK_596DC8CFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_sortie ADD CONSTRAINT FK_596DC8CFCC72D953 FOREIGN KEY (sortie_id) REFERENCES sortie (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_log DROP FOREIGN KEY FK_ED15DF2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_log DROP FOREIGN KEY FK_ED15DF2CC72D953
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F26BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F26AB213CC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2F6BD1646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F25346EAE1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2CD77356E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649F6BD1646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_sortie DROP FOREIGN KEY FK_596DC8CFA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_sortie DROP FOREIGN KEY FK_596DC8CFCC72D953
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE etat
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lieu
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE motif_annulation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification_log
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reset_password_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE site
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sortie
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_sortie
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
