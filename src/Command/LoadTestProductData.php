<?php
namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'app:load-test-product-data')]
class LoadTestProductData extends Command
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
        $connection->executeStatement($platform->getTruncateTableSQL('product', true));

        $handle = fopen($filePath, 'r');
        $count = 0;

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);
            if ($data === null) {
                $output->writeln("<error>Invalid JSON line skipped</error>");
                continue;
            }

            try {
                $product = new Product();
                $product->setAsin($data['parent_asin']);
                $product->setTitle($data['title']);
                $product->setAverageRating((string)$data['average_rating']);
                $product->setRatingNumber($data['rating_number']);
                $product->setDescription(implode("\n", $data['description']));

                $price = (float)($data['price'] ?? 0);
                $product->setPrice($price);

                // Set thumbnail and image (if available)
                if (!empty($data['images'])) {
                    if(isset($data['images'][0]['thumb'])) {
                        $product->setThumbnail($data['images'][0]['thumb']);
                    }
                    if(isset($data['images'][0]['large'])) {
                        $product->setImage($data['images'][0]['large']);
                    }
                }

                $product->setDetails($data['details']);
                $this->entityManager->persist($product);
            } catch (\Exception $e) {
                $output->writeln("<error>Error processing product: " . $e->getMessage() . "</error>");
                continue;
            }
                
                $count++;
                if ($count % 100 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $output->writeln("Processed $count products");
            }
        }

        fclose($handle);
        $this->entityManager->flush();
        $output->writeln("Finished loading $count products");

        return Command::SUCCESS;
    }
}
