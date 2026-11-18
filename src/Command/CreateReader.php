<?php

namespace App\Command;

use App\Entity\Person;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reader:create',
    description: 'Creates a new reader (person) who can use the library.',
)]
class CreateReader extends Command
{
    private const int NICKNAME_MAX_LENGTH = 100;
    private const int FULL_NAME_MAX_LENGTH = 200;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PersonRepository $personRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nickname', InputArgument::OPTIONAL, 'Short name shown on the welcome page')
            ->addArgument('fullName', InputArgument::OPTIONAL, 'Full name of the reader');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getArgument('nickname')) {
            $input->setArgument('nickname', $io->ask('Nickname', null, fn (?string $value) => $this->validate($value, 'Nickname', self::NICKNAME_MAX_LENGTH)));
        }

        if (!$input->getArgument('fullName')) {
            $input->setArgument('fullName', $io->ask('Full name', null, fn (?string $value) => $this->validate($value, 'Full name', self::FULL_NAME_MAX_LENGTH)));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $nickname = $this->validate($input->getArgument('nickname'), 'Nickname', self::NICKNAME_MAX_LENGTH);
            $fullName = $this->validate($input->getArgument('fullName'), 'Full name', self::FULL_NAME_MAX_LENGTH);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        }

        if ($this->personRepo->findOneBy(['nickname' => $nickname])) {
            $io->error(sprintf('A reader with the nickname "%s" already exists.', $nickname));
            return Command::FAILURE;
        }

        $reader = new Person();
        $reader->setNickname($nickname);
        $reader->setFullName($fullName);

        $this->entityManager->persist($reader);
        $this->entityManager->flush();

        $io->success(sprintf('Reader "%s" (%s) created with id %d.', $nickname, $fullName, $reader->getId()));

        return Command::SUCCESS;
    }

    private function validate(?string $value, string $field, int $maxLength): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \RuntimeException(sprintf('%s cannot be empty.', $field));
        }

        if (mb_strlen($value) > $maxLength) {
            throw new \RuntimeException(sprintf('%s cannot be longer than %d characters.', $field, $maxLength));
        }

        return $value;
    }
}
