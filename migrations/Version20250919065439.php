<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250919065439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE person (id SERIAL NOT NULL, nickname VARCHAR(100) NOT NULL, full_name VARCHAR(200) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE book ALTER is_reference DROP DEFAULT');
        $this->addSql('ALTER TABLE read_log ADD reader_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE read_log ADD CONSTRAINT FK_1ED389971717D737 FOREIGN KEY (reader_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1ED389971717D737 ON read_log (reader_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE read_log DROP CONSTRAINT FK_1ED389971717D737');
        $this->addSql('DROP TABLE person');
        $this->addSql('ALTER TABLE book ALTER is_reference SET DEFAULT false');
        $this->addSql('DROP INDEX IDX_1ED389971717D737');
        $this->addSql('ALTER TABLE read_log DROP reader_id');
    }
}
