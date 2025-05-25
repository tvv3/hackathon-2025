<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Http\Message\UploadedFileInterface;
class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 1;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
       
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {   
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        $flash_success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $flash_error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $userId = $_SESSION['user']['id']?? null; // TODO: obtain logged-in user ID from session
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

       $queryParams = $request->getQueryParams();
       $year = isset($queryParams['year']) ? (int)$queryParams['year'] : null;
       $month = isset($queryParams['month']) ? (int)$queryParams['month'] : null;

         //var_dump($year);
        $totalPages=1;
         $arr = $this->expenseService->list($userId, $page, $pageSize, $year, $month);
         $expenses=$arr[0];
         $totalPages=$arr[1]<=0?1:$arr[1];
        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total' => $totalPages,
            'flash_success' =>$flash_success,
            'flash_error' =>$flash_error,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        return $this->render($response, 'expenses/create.twig', ['categories' => []]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $expense = ['id' => 1];

        return $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => []]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

     $userId = $_SESSION['user']['id'] ?? null;
     $expenseId = (int)($routeParams['id'] ?? 0);

     if (!$userId || !$expenseId) {
        $response->getBody()->write('Bad request');
        return $response->withStatus(400);//bad request
      }

    // Load the expense
     $expense = $this->expenseService->getExpenseById($expenseId);

     if (!$expense) {
        $response->getBody()->write('Expense not found');
        return $response->withStatus(404);
      }

    // Check if the expense belongs to the logged-in user
      if ($expense->userId !== $userId) {
        $response->getBody()->write('Forbidden');
        return $response->withStatus(403);
      }

    // Delete the expense
       $expenseIdDeleted=$expenseId;
       $this->expenseService->deleteExpense($expenseId);

       $_SESSION['flash_success'] = 'Expense with id='. $expenseIdDeleted.' deleted successfully.';

    // Redirect to /expenses
      return $response
        ->withHeader('Location', '/expenses')
        ->withStatus(302);
   }
    

    public function importFromCsv(Request $request, Response $response): Response
    {
        $userId=(int)$_SESSION['user']['id']??null;
        $file = $request->getUploadedFiles();
        $csvFile = $file['csv'] ?? null;

        if (($userId)&&($csvFile instanceof UploadedFileInterface)&&($csvFile->getError() === UPLOAD_ERR_OK))
        {
         $filename = $csvFile->getClientFilename();
         $extension = pathinfo($filename, PATHINFO_EXTENSION);

         if (strtolower($extension) !== 'csv') {
           $_SESSION['flash_error'] = 'No import! The file must be a .csv file!';

        return $response
            ->withHeader('Location', '/expenses')
            ->withStatus(302);  
        }


        $arr=$this->expenseService->importFromCsv($userId,  $csvFile);
        $message = sprintf('Imported: %d, Duplicates: %d', $arr[0], $arr[1]);

        //$response->getBody()->write($message);
         $_SESSION['flash_success'] = $message;

        return $response
            ->withHeader('Location', '/expenses')
            ->withStatus(302);
             /*
            $response->getBody()->write($message);
    return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
        */
        }
         $_SESSION['flash_error'] = 'No import!';

        return $response
            ->withHeader('Location', '/expenses')
            ->withStatus(302);
        /*
        $response->getBody()->write('No import');
    return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
        */
    }
  
}
