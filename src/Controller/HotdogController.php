<?php

namespace App\Controller;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Codewithkyrian\Transformers\Pipelines\pipeline;

#[Route('/hotdog')]
class HotdogController extends AbstractController
{
    #[Route('/', name: 'hotdog_index')]
    public function index(): Response
    {
        return $this->render('hotdog/index.html.twig', [
            'controller_name' => 'HotdogController'
        ]);
    }

    #[Route('/handle', name: 'hotdog_handle')]
    public function handle(Request $request): Response
    {
        $image = $request->files->get('image');
        if (!$image) {
            throw $this->createNotFoundException('No image was uploaded');
        }

        // Generate a unique filename
        $newFilename = uniqid().'.'.$image->guessExtension();

        $labels = [];
        $isHotdog = false;

            // Move the file to the uploads directory
            $image->move(
                $this->getParameter('kernel.project_dir').'/public/uploads',
                $newFilename
            );


            // Process the image to generate labels
            Transformers::setup()->setImageDriver(ImageDriver::GD);
            $classifier = pipeline('image-classification');
            $result = $classifier($this->getParameter('kernel.project_dir').'/public/uploads/'.$newFilename, 3);
            foreach ($result as $item) {
                $labels[] = $item['label'];
                if (str_contains($item['label'], 'hotdog')) {
                    $isHotdog = true;
                }
            }


        // Generate the public path for the template
        $imagePath = '/uploads/'.$newFilename;

        $imageName = $image->getClientOriginalName();
        return $this->render('hotdog/handle.html.twig', [
            'imageName' => $imageName,
            'imagePath' => $imagePath,
            'labels' => $labels,
            'isHotdog' => $isHotdog
        ]);
    }
}
