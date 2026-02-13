<?php

namespace App\Command;

use App\Enum\StatusEnum;
use App\Factory\WorkLogFactory;
use App\Parser\LogFileParser;
use App\Repository\WorkLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import:work-log')]
class ImportWorkLogCommand extends Command
{
    public function __construct(
        private readonly LogFileParser $parser,
        private readonly WorkLogRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->parser->parse() as $dto) {
            $existing = $this->repository->findOneByDate($dto->date);
            if (!$existing) {
                // Pas encore de log → création
                $this->em->persist(WorkLogFactory::createFromDto($dto));
            } elseif (StatusEnum::IN_PROGRESS === $existing->getStatus()) {
                // Existe et en cours → mise à jour
                $existing->setStartTime($dto->startTime);
                $existing->setEndTime($dto->endTime);
                $existing->setWorkedMinutes($dto->workedMinutes);
                $existing->setStatus($dto->status);

                $this->em->persist($existing);
            }
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
