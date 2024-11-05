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

#[AsCommand(name: 'app:load-test-review-data')]
class LoadTestReviewData extends Command
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
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the JSONL file')
            ->setDescription('Load test data from a JSONL file into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('file');
        $output->writeln("Loading test data from $filePath...");

        if (!file_exists($filePath)) {
            $output->writeln("<error>File not found: $filePath</error>");
            return Command::FAILURE;
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        // $connection->executeStatement($platform->getTruncateTableSQL('review', true));

        $handle = fopen($filePath, 'r');
        $count = 0;

        // Fetch all products from the db in memory with their asin as key and id as value
        $productsIndex = [];
        $productRepository = $this->entityManager->getRepository(Product::class);
        $products = $productRepository->findBy([], ['asin' => 'ASC']);
        foreach ($products as $product) {
            $productsIndex[$product->getAsin()] = $product;
        }

        $output->writeln("Found " . count($productsIndex) . " products");

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);
            if ($data === null) {
                $output->writeln("<error>Invalid JSON line skipped</error>");
                continue;
            }

            try {

                // Find or create the product
                if(!isset($productsIndex[$data['parent_asin']])) {
                    // Product not found, skip this review
                    $output->writeln("<error>Product not found for review: " . $data['parent_asin'] . "</error>");
                    continue;
                }

                $review = new Review();
                $review->setRating((string)$data['rating']);
                $review->setTitle($data['title']);
                $review->setText($data['text']);
                $review->setVerifiedPurchase($data['verified_purchase']);
                $review->setSortTimestamp(new \DateTime('@' . ($data['timestamp'] / 1000)));

                $review->setProduct($productsIndex[$data['parent_asin']]);

                $this->entityManager->persist($review);
            } catch (\Exception $e) {
                $output->writeln("<error>Error processing review: " . $e->getMessage() . "</error>");
                continue;
            }
                
            $count++;
            if ($count % 10000 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $output->writeln("Processed $count reviews");
            }
        }

        fclose($handle);
        $this->entityManager->flush();
        $output->writeln("Finished loading $count reviews");

        return Command::SUCCESS;
    }
}
