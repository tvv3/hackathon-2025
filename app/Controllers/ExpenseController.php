<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Http\Message\UploadedFileInterface;
use DateTimeImmutable;
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
         
         if ((array_key_exists(2,$arr))&&(is_array($arr[2]))&&($arr[2]!=[]))
         {
           $years=$arr[2];
         }
         else 
         {//if i delete all data arr[2] can be []; then i will sent the implicitYear to the twig
         $implicitYear=(int)date('Y');
         $years=array('0'=>$implicitYear);//at least one year will be send to the twig
         }
         //$years=array_reverse($years);
         $first_year=$years[0];
        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total' => $totalPages,
            'flash_success' =>$flash_success,
            'flash_error' =>$flash_error,
            'years'=>$years,
            'first_year'=>$first_year,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        $flash_success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $flash_error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        $categories = $_ENV['APP_CATEGORIES'] ?? null;
        if (!$categories) {$categories=["Any"];}
        else{
          $categories=json_decode($categories, true);
        }
        //var_dump($categories);
        return $this->render($response, 'expenses/create.twig', 
                ['categories' => $categories,
                 'flash_success'=>$flash_success,
                 'flash_error'=>$flash_error,
                                     ]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $data = $request->getParsedBody();
        $userId=$_SESSION['user']['id']??null;
        if (!$userId)   
        {
            //$_SESSION['flash_error']='You are not logged in!';
            //mytodo: to add flash messages to the login twig; to unset them at showlogin
            //redirect to login
            return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
        }
        
       // Sanitize 
       $expenseData = [
         'description' => trim($data['description'] ?? ''),
         'amount'      => intval($data['amount']) ?? 0,
         'date'        => $data['date']?? null,//same format from twig and below
         'category'    => trim($data['category'] ?? ''),
        ];

        $expenseData['date'] = DateTimeImmutable::createFromFormat('Y-m-d', $expenseData['date']);

        if ($expenseData['date']===false)
        {
            $_SESSION['flash_error']=$data['date'].'The format for the date is wrong!';
            //mytodo: to add flash messages to the expenses.create.twig; to unset them at showlogin
            //redirect to login
            return $response
            ->withHeader('Location', '/expenses/create')
            ->withStatus(302); 
        }
        //we have step="0.01" in twig so if i put 12.35 the price will pe 0.12 euro and in database 12 cents!
        //mytodo: we might have to adjust the import csv 
        //so here it must be an int value introduced 
        if ($expenseData['amount']<=0) 
        {
            $_SESSION['flash_error']=$expenseData['amount'].'The amount is not greater than 0!';
            //mytodo: to add flash messages to the expenses.create.twig; to unset them at showlogin
            //redirect to login
            return $response
            ->withHeader('Location', '/expenses/create')
            ->withStatus(302); 
        }
        if ($expenseData['amount']*100!=floor($expenseData['amount']*100))
        {
           $_SESSION['flash_error']='The amount can have at most 2 decimals!';
            //mytodo: to add flash messages to the expenses.create.twig; to unset them at showlogin
            //redirect to login
            return $response
            ->withHeader('Location', '/expenses/create')
            ->withStatus(302); 
        }
             //public function create(
        
        $message= $this->expenseService->create($userId, $expenseData['amount'],
              $expenseData['description'],$expenseData['date'],
              $expenseData['category']            
          );

        if ($message!='saved')
        {
        $_SESSION['flash_error']=$message;
        // Option 1: Redirect with success message
        return $response
            ->withHeader('Location', '/expenses/create')
            ->withStatus(302);
        }
        //else
         $_SESSION['flash_success']='Expense added successfully!';
        // Option 1: Redirect with success message
        return $response
            ->withHeader('Location', '/expenses')
            ->withStatus(302);

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
