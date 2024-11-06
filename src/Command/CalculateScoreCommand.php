<?php
namespace App\Command;

use App\Entity\Product;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use function Codewithkyrian\Transformers\Pipelines\pipeline;


#[AsCommand(name: 'app:score')]
class CalculateScoreCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('asin', InputArgument::REQUIRED, 'ASIN to calculate score for')
            ->setDescription('Calculate score for a given ASIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $asin = $input->getArgument('asin');
        $output->writeln("Calculating score for ASIN: $asin");

        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['asin' => $asin]);
        if (!$product) {
            $output->writeln("Product not found");
            return Command::FAILURE;
        }

        $output->writeln("Analyzing product: ".$product->getTitle());
        $reviews = $product->getReviews();

        $output->writeln("Found " . $reviews->count() . " reviews");

        $pipe = pipeline('sentiment-analysis');

        $positive = 0;
        $negative = 0;

        $startTime = microtime(true);
        
        foreach ($reviews as $review) {
            $out = $pipe($review->getText());
            if ($out['label'] == 'POSITIVE') {
                $positive++;
            } else {
                $negative++;
            }
        }
        
        $output->writeln("Positive: $positive, Negative: $negative, Score: " . ($positive - $negative) ." (".round($positive / $reviews->count() * 100, 2)."% positive)");
        $output->writeln("Time taken: " . (microtime(true) - $startTime) . " seconds");
        $output->writeln("Reviews per second: " . $reviews->count() / (microtime(true) - $startTime));
        return Command::SUCCESS;
    }
}
