<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add book.owner_id: each book belongs to one reader';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book ADD owner_id INT DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE book SET owner_id = (
                SELECT l.reader_id FROM read_log l
                WHERE l.book_id = book.id AND l.reader_id IS NOT NULL
                GROUP BY l.reader_id
                ORDER BY COUNT(*) DESC, l.reader_id ASC
                LIMIT 1
            )
        SQL);

        $this->addSql('UPDATE book SET owner_id = (SELECT MIN(id) FROM person) WHERE owner_id IS NULL');

        $this->addSql('ALTER TABLE book ALTER owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A3317E3C61F9 FOREIGN KEY (owner_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CBE5A3317E3C61F9 ON book (owner_id)');
    }

    public function preUp(Schema $schema): void
    {
        $books = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM book');
        $persons = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM person');

        $this->abortIf($books > 0 && $persons === 0, 'There are books but no readers to own them: create a reader first (make create-reader).');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP CONSTRAINT FK_CBE5A3317E3C61F9');
        $this->addSql('DROP INDEX IDX_CBE5A3317E3C61F9');
        $this->addSql('ALTER TABLE book DROP owner_id');
    }
}
