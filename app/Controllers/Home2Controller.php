<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class Home2Controller{
    protected Twig $view;

    public function __construct(Twig $view)
    {
        $this->view=$view;
    }

    public function sayHello(Request $request, Response $response, array $args):Response
    {
        $lang=$args['lang'] ?? 'ro';
        $name="Hello";
        if ($lang==='en') {$name='Hello World!';}
        return $this->view->render($response, 'mytests2/home2.twig', ['name'=>$name
        //, 'currentUserId'=>$_SESSION['user']['id'], 'currentUserName'=>$_SESSION['user']['username']
        ]);
    }
}