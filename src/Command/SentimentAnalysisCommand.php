<?php
namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use function Codewithkyrian\Transformers\Pipelines\pipeline;


#[AsCommand(name: 'app:sentiment-analysis')]
class SentimentAnalysisCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('text', InputArgument::REQUIRED, 'Text to analyze')
            ->setDescription('Run Sentiment Analysis on a given text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = $input->getArgument('text');
        $output->writeln("Analyzing text: $text");


        $pipe = pipeline('sentiment-analysis');
        $out = $pipe($text);
        
        $output->writeln("Sentiment: " . $out['label'] . " (" . $out['score'] . ")");

        return Command::SUCCESS;
    }
}
