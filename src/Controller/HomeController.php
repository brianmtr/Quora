<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(QuestionRepository $questionRepo, ManagerRegistry $doctrine): Response
    {
        $questions = $questionRepo->getQuestionWithAuthors();
        return $this->render('home/index.html.twig', [
            'questions' => $questions,
        ]);
    }
}
