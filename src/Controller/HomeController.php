<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $questions = [
            [   
                'id' => '1',
                'title' => 'Je suis un titre',
                'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Assumenda, est veniam ipsa nostrum beatae quam amet quisquam reiciendis maxime, facilis sit quas eveniet consequatur maiores perferendis vitae laudantium velit ipsam.',
                'rating' => 20,
                'author' => [
                'name' => 'Jean Dupont',
                    'avatar' => 'https://randomuser.me/api/portraits/men/77.jpg'
                ],
                'nbrOfResponse' => 15
            ],
            [   
                'id' => '2',
                'title' => 'Je suis un titre',
                'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Assumenda, est veniam ipsa nostrum beatae quam amet quisquam reiciendis maxime, facilis sit quas eveniet consequatur maiores perferendis vitae laudantium velit ipsam.',
                'rating' => 0,
                'author' => [
                'name' => 'Sebastien Roder',
                    'avatar' => 'https://randomuser.me/api/portraits/men/46.jpg'
                ],
                'nbrOfResponse' => 15
            ],
            [   
                'id' => '3',
                'title' => 'Je suis un titre',
                'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Assumenda, est veniam ipsa nostrum beatae quam amet quisquam reiciendis maxime, facilis sit quas eveniet consequatur maiores perferendis vitae laudantium velit ipsam.',
                'rating' => -14,
                'author' => [
                'name' => 'Lea Chevalier',
                    'avatar' => 'https://randomuser.me/api/portraits/women/72.jpg'
                ],
                'nbrOfResponse' => 12
            ],

        ];


        return $this->render('home/index.html.twig', [
            'questions' => $questions,
        ]);
    }
}
