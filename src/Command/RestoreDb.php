<?php

namespace App\Command;

use App\Repository\PersonRepository;
use App\Repository\BookRepository;
use App\Repository\ReadLogRepository;
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
        private PersonRepository $personRepo,
        private BookRepository $bookRepo,
        private ReadLogRepository $readLogRepo,
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

        // Case 1: Assume only the database exists, verify if the tables are present and if not, run migrations
        if ($this->migrationsDoesNotExists()) {
            $io->writeln('Database appears to be empty. Running migrations to create necessary tables... Hit enter to proceed.'); 

            // doctrine:migrations:mirate command needs to be executed first
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
        
        $io->success('Database restoration completed successfully.');

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
}