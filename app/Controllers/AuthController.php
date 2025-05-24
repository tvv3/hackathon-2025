<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        //validari
       $parsedBody = $request->getParsedBody();
       $username = $parsedBody['username'] ?? null;
       $password = $parsedBody['password'] ?? null;

        if ((empty($username))||(strlen($username)<4))
        $errors['username']="The username must be at least 4 characters long!";

        if ((empty($password))||(strlen($password)<8))
        $errors['password']="The password must be at least 8 characters long!";

        //verifica 1 numar in parola

        $password_ok=false;
        $password_arr=str_split($password);
        foreach($password_arr as $c)
        {
            if (in_array($c,[0,1,2,3,4,5,6,7,8,9]))
            {$password_ok=true; break;}
        }

        if (!$password_ok)
        {
            if (isset($errors['password']))
            { $errors['password'].=" "."The password must contain at least 1 number!";}

            else
            {$errors['password']+="The password must contain at least 1 number!";}
        }
        

        if (isset($errors))
        {
            //render /register page and show corresponding error messages.
           return $this->render($response, 'auth/register.twig', ['errors'=>$errors]);
           //mytodo: verify error display 
        }
        //else
        // TODO: call corresponding service to perform user registration
       $user= $this->authService->register($username, $password);
        if (!isset($user))
        {
            $errors['username']='The username must be unique!';
            return $this->render($response, 'auth/register.twig', ['errors'=>$errors]);
          
        }
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
          
        // TODO: call corresponding service to perform user login, handle login failures
           $parsedBody = $request->getParsedBody();
           $username = $parsedBody['username'] ?? null;
           $password = $parsedBody['password'] ?? null;

           if ((!(empty($username)))&&(!(empty($password))))
           {
           $ok=$this->authService->attempt($username,$password);
           if ($ok) {return $response->withHeader('Location', '/')->withStatus(302);}// / cu 302
           //else
           return $this->render($response, 'auth/login.twig', [
            'error' => 'Invalid username or password',
            'username' => $username // pre-fill field
        ]);
    }

    // Missing fields
    return $this->render($response, 'auth/login.twig', [
        'error' => 'Please enter both username and password'
    ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session
         unset($_SESSION['user']);
         session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
