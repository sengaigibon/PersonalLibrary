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

#[AsCommand(
    name: 'app:db:backup',
    description: 'Creates a backup of the library database in JSON format.',
)]
class BackupDb extends Command
{
    public function __construct(
        private PersonRepository $personRepo,
        private BookRepository $bookRepo,
        private ReadLogRepository $readLogRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $backupDir = getenv('HOME') . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $date = new \DateTime();
        $backupFile = $backupDir . '/' . $date->format('Ymd_His') .  '_personal-library-backup.json';
        
        $backup = [];
        $backup['persons'] = $this->personRepo->findAll();
        $backup['books'] = $this->bookRepo->findAll();
        $backup['readLogs'] = $this->readLogRepo->findAll();  

        try {
            $jsonData = json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);    
            file_put_contents($backupFile, $jsonData);
        } catch (\Exception $e) {
            $io->error('Failed to create backup: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Database backup completed successfully.');

        return Command::SUCCESS;
    }
}