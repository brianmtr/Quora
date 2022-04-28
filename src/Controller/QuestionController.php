<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Question;
use App\Entity\Vote;
use App\Form\CommentType;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class QuestionController extends AbstractController
{
  #[Route('/question/ask', name: 'question_form')]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function index(Request $request, EntityManagerInterface $em): Response
  {

    $user = $this->getUser();
    $question = new Question();
    $formQuestion = $this->createForm(QuestionType::class, $question);
    $formQuestion->handleRequest($request);

    if ($formQuestion->isSubmitted() && $formQuestion->isValid()) {
      $question->setNbrOfResponse(0);
      $question->setRating(0);
      $question->setAuthor($user);
      $question->setcreatedAt(new \DateTimeImmutable());
      $em->persist($question);
      $em->flush();
      $this->addFlash('success', 'Votre question a été ajoutée');
      return $this->redirectToRoute('home');
    }

    return $this->render('question/index.html.twig', [
      'form' => $formQuestion->createView(),
    ]);
  }

  #[Route('/question/{id}', name: 'question_show')]
  public function show( Request $request, QuestionRepository $questionRepo, int $id, EntityManagerInterface $em): Response
  {
    $question = $questionRepo->getQuestionWithCommentsAndAuthors($id);

    $options = [
      'question' => $question
    ];

    $user = $this->getUser();

    if ($user) {
      $comment = new Comment();
      $commentForm = $this->createForm(CommentType::class, $comment);
      $commentForm->handleRequest($request);
      if ($commentForm->isSubmitted() && $commentForm->isValid()) {
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setRating(0);
        $comment->setQuestion($question);
        $comment->setAuthor($user);
        $question->setNbrOfResponse($question->getNbrOfResponse() + 1);
        $em->persist($comment);
        $em->flush();
        $this->addflash('success', 'Votre réponse a bien été ajouté.');
        return $this->redirect($request->getUri());
      }
      $options['form'] = $commentForm->createView();
    }

    // $question =  [
    //   'title' => 'Je suis un titre',
    //   'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Assumenda, est veniam ipsa nostrum beatae quam amet quisquam reiciendis maxime, facilis sit quas eveniet consequatur maiores perferendis vitae laudantium velit ipsam.',
    //   'rating' => 20,
    //   'author' => [
    //   'name' => 'Jean Dupont',
    //       'avatar' => 'https://randomuser.me/api/portraits/men/77.jpg'
    //   ],
    //   'nbrOfResponse' => 15
    // ];
   

    return $this->render('question/show.html.twig', $options);
  }

  #[Route('/comment/rating/{id}/{score}', name: 'comment_rating')]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function ratingComment(Request $request, Comment $comment, int $score, EntityManagerInterface $em) {
    $comment->setRating($comment->getRating() + $score);
    $em->flush();
    $referer = $request->server->get('HTTP_REFERER');
    return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
  }

  #[Route('/question/rating/{id}/{score}', name: 'question_rating')]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function ratingQuestion(Request $request, Question $question, int $score, EntityManagerInterface $em) {

    $question->setRating($question->getRating() + $score);
    
      $em->flush();      
      $referer = $request->server->get('HTTP_REFERER');
      return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }



  }
