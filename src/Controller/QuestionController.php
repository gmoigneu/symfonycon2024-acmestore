<?php

namespace App\Controller;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Codewithkyrian\Transformers\Pipelines\pipeline;

#[Route('/question')]
class QuestionController extends AbstractController
{
    #[Route('/', name: 'question_index')]
    public function index(): Response
    {
        return $this->render('question/index.html.twig', [
            'controller_name' => 'QuestionController'
        ]);
    }

    #[Route('/handle', name: 'question_handle')]
    public function handle(Request $request): Response
    {
        $question = $request->request->get('question');

        $generator = pipeline('text2text-generation', 'Xenova/flan-t5-small');
        $result = $generator($question,
            maxNewTokens: 256, 
            repetitionPenalty: 1.6,
            temperature: 0.7
        );
        $answer = $result[0]['generated_text'];

        return $this->render('question/handle.html.twig', [
            'question' => $question,
            'answer' => $answer
        ]);
    }
}
