<?php

namespace App\Controller;

use App\Form\QuestionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class QuestionController extends AbstractController
{
  #[Route('/question/ask', name: 'question_form')]
  public function index(Request $request): Response
  {

    $formQuestion = $this->createForm(QuestionType::class);

    $formQuestion->handleRequest($request);

    if ($formQuestion->isSubmitted() && $formQuestion->isValid()) {
      
    }

    return $this->render('question/index.html.twig', [
      'form' => $formQuestion->createView(),
    ]);
  }

  #[Route('/show/{id}', name: 'question_show')]
  public function show(Request $request, string $id): Response
  {
    $question =  [
      'title' => 'Je suis un titre',
      'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Assumenda, est veniam ipsa nostrum beatae quam amet quisquam reiciendis maxime, facilis sit quas eveniet consequatur maiores perferendis vitae laudantium velit ipsam.',
      'rating' => 20,
      'author' => [
      'name' => 'Jean Dupont',
          'avatar' => 'https://randomuser.me/api/portraits/men/77.jpg'
      ],
      'nbrOfResponse' => 15
    ];
   

    return $this->render('question/show.html.twig', [
      'question' =>$question,
    ]);
  }
}