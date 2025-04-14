<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414125557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation ADD creator_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E961220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8A8E26E961220EA6 ON conversation (creator_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E961220EA6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8A8E26E961220EA6 ON conversation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation DROP creator_id
        SQL);
    }
}
