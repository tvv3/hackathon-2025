<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        // TODO: add necessary services here and have them injected by the DI container
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        $parsedBody = $request->getParsedBody();
        // TODO: load the currently logged-in user
        //$current_user_id=$_SESSION['user']['id'];
       // $current_username=$_SESSION['user']['username'];
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month

        return $this->render($response, 'dashboard.twig', [
           // 'currentUserId'       => $current_user_id,
           // 'currentUserName'      => $current_username,
            'alerts'                => [],
            'totalForMonth'         => [],
            'totalsForCategories'   => [],
            'averagesForCategories' => [],
        ]);
    }
}
