<?php

namespace App\Command;

use App\Service\HolidayManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:holidays:generate',
    description: 'Generate Madagascar public holidays for a given year',
)]
class HolidaysGenerateCommand extends Command
{
    public function __construct(private readonly HolidayManager $holidayManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('year', InputArgument::OPTIONAL, 'Year to generate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yearInput = $input->getArgument('year');

        $year = $yearInput
            ? (int) $yearInput
            : (int) date('Y');

        try {
            $this->holidayManager->generateMadagascarHolidays($year);
            $output->writeln("<info>Holidays generated for $year</info>");
        } catch (\Exception $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
