<?php

namespace App\Command;

use App\Entity\Person;
use App\Entity\Book;
use App\Entity\ReadLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[AsCommand(
    name: 'app:db:restore',
    description: 'Restores the library database from a JSON backup file.',
)]
class RestoreDb extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Suppress Doctrine deprecation warnings globally for this command
        error_reporting(error_reporting() & ~E_USER_DEPRECATED);
        
        $this->io = $io = new SymfonyStyle($input, $output);

        $backupDir = getenv('HOME') . '/backups';
        if (!is_dir($backupDir)) {
            $io->error('Backup directory does not exist: ' . $backupDir);
            return Command::FAILURE;
        }

        // Read all files in the backup directory and find the most recent one
        $files = glob($backupDir . '/*_personal-library-backup.json');
        if (empty($files)) {
            $io->error('No backup files found in directory: ' . $backupDir);
            return Command::FAILURE;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $latestBackupFile = $files[0];

        // Verify if the tables are present and if not, run migrations
        if ($this->migrationsDoesNotExists()) {
            $io->writeln('Database appears to be empty. Running migrations to create necessary tables... Hit enter to proceed.'); 

            if (!$this->doMigrations()) {
                $io->error('Failed to run database migrations.');
                return Command::FAILURE;
            }

            $io->success('Database migrations completed successfully.');
        }

        // Now proceed to restore data. Prompt user for confirmation
        $io->writeln('Latest backup file found: ' . $latestBackupFile);
        if (!$io->confirm('Are you sure you want to restore the database from this backup? This will overwrite existing data.', false)) {
            $io->warning('Database restore cancelled by user.');
            return Command::SUCCESS;
        }

        try {
            $jsonData = file_get_contents($latestBackupFile);
            $backup = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $io->error('Failed to read or decode backup file: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        try {

            $this->clearExistingData();

            $this->restorePersons($backup['persons']);
            $this->restoreBooks($backup['books']);
            $this->restoreReadLogs($backup['readLogs']);
            
            $this->resetSequences();

        } catch (\Exception $e) {
            $io->error('Failed to restore data: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $personCount = count($backup['persons']);
        $bookCount = count($backup['books']);
        $readLogCount = count($backup['readLogs']);
        
        $io->success(sprintf(
            'Database restoration completed successfully! Restored %d person(s), %d book(s), and %d read log(s).',
            $personCount,
            $bookCount,
            $readLogCount
        ));

        return Command::SUCCESS;
    }

    private function doMigrations(): int
    {
        $application = $this->getApplication();
        $input = new ArrayInput(['command' => 'doctrine:migrations:migrate', '--no-interaction' => true]);

        $output = new BufferedOutput();

        try {
            $result = $application->doRun($input, $output);
        } catch (\Exception $e) {
            $this->io->error('Failed to check migrations: ' . $e->getMessage());
            exit(1);
        }

        return !$result;
    }

    private function migrationsDoesNotExists(): bool
    {
        $application = $this->getApplication();
        $input = new ArrayInput(['command' => 'doctrine:migrations:current', '--no-interaction' => true]);

        $output = new BufferedOutput();
        
        try {
            $application->doRun($input, $output);
        } catch (\Exception $e) {
            $this->io->error('Failed to check migrations: ' . $e->getMessage());
            exit(1);
        }

        $outputContent = $output->fetch();

        return str_contains($outputContent, 'No migration executed yet');
    }

    private function clearExistingData(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\ReadLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Book')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Person')->execute();
        $this->entityManager->clear();
    }

    private function restorePersons(array $personsData): void
    {
        $connection = $this->entityManager->getConnection();
        
        foreach ($personsData as $personData) {
            $connection->insert('person', [
                'id' => $personData['id'],
                'nickname' => $personData['nickname'],
                'full_name' => $personData['fullName']
            ]);
        }
    }

    private function restoreBooks(array $booksData): void
    {
        $connection = $this->entityManager->getConnection();
        
        foreach ($booksData as $bookData) {
            $connection->insert('book', [
                'id' => $bookData['id'],
                'title' => $bookData['title'],
                'author' => $bookData['author'],
                'isbn' => $bookData['isbn'],
                'pages' => $bookData['pages'],
                'purchase_date' => $bookData['purchaseDate'],
                'is_reference' => (int) $bookData['isReference']
            ]);
        }
    }

    private function restoreReadLogs(array $readLogsData): void
    {
        $connection = $this->entityManager->getConnection();
        
        foreach ($readLogsData as $readLogData) {
            $connection->insert('read_log', [
                'id' => $readLogData['id'],
                'start_date' => $readLogData['startDate'],
                'finish_date' => $readLogData['finishDate'],
                'rating' => $readLogData['rating'],
                'notes' => $readLogData['notes'],
                'book_id' => $readLogData['book'],
                'reader_id' => $readLogData['reader']
            ]);
        }
    }

    private function resetSequences(): void
    {
        $connection = $this->entityManager->getConnection();
        
        // Reset sequences for PostgreSQL
        $connection->executeStatement("SELECT setval('person_id_seq', (SELECT MAX(id) FROM person))");
        $connection->executeStatement("SELECT setval('book_id_seq', (SELECT MAX(id) FROM book))");
        $connection->executeStatement("SELECT setval('read_log_id_seq', (SELECT MAX(id) FROM read_log))");
        
        // Restore normal ID generation
        $this->entityManager->getClassMetadata(Person::class)->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $this->entityManager->getClassMetadata(Book::class)->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $this->entityManager->getClassMetadata(ReadLog::class)->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
    }
}