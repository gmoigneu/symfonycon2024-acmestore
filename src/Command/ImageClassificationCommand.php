<?php
namespace App\Command;

use App\Entity\Product;
use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use function Codewithkyrian\Transformers\Pipelines\pipeline;


#[AsCommand(name: 'app:image-classification')]
class ImageClassificationCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('image', InputArgument::REQUIRED, 'URL of the image to analyze')
            ->setDescription('Run Image Classification on a given image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Transformers::setup()
            ->setImageDriver(ImageDriver::GD);

        $image = $input->getArgument('image');
        $output->writeln("Analyzing image: $image");


        $classifier = pipeline('image-classification');
        $result = $classifier($image, 3);
        
        $output->writeln("Classification: ");
        foreach ($result as $item) {
            $output->writeln("  " . $item['label'] . " (" . $item['score'] . ")");
        }   

        return Command::SUCCESS;
    }
}
