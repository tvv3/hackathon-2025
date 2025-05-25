<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;
//noi
use Exception;
class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(int $userId, int $pageNumber,
     int $pageSize, ?int $year, ?int $month): array
    {     
        //var_dump($year);
        // TODO: implement this and call from controller to obtain paginated list of expenses
        $arr= $this->expenses->getExpensesByUserPaginated($userId,$pageNumber, $pageSize, $year, $month);
        
        $years=$this->expenses->listExpenditureYears($userId);
        $arr[]=$years;
        return $arr;
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist

        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->id, $date, $category, (int)$amount, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
    }
/*
    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
*/
    public function importFromCsv(int $userId, UploadedFileInterface $csvFile): array
  {
    $importedCount = 0;
    $csvIgnoredDuplicates=0;
    // Open the CSV file stream
    $stream = $csvFile->getStream();

    // Rewind stream to start
    $stream->rewind();

    // Get the file handle from the stream
    $handle = fopen('php://temp', 'r+');
    if ($handle === false) {
        throw new Exception('Failed to open temp stream');
    }

    // Copy stream content into temp handle (because PSR stream may not support fgetcsv)
    while (!$stream->eof()) {
        fwrite($handle, $stream->read(1024));
    }
    rewind($handle);
    

    while (($row = fgetcsv($handle)) !== false) {
        // Assuming CSV columns in this order:
        // date, category, amount (decimal), description

        // Validate row length
        if (count($row) < 4) {
            // Skip invalid row or throw exception depending on your needs
            continue;
        }

        [$dateStr, $description, $amount, $category] = $row;
        //(date,description,amount,category)
        // Parse and validate date (adjust format if needed)
        try {
            $date = new DateTimeImmutable($dateStr);
        } catch (Exception $e) {
            // Invalid date format, skip this row
            continue;
        }

        // Validate category (you might want to check if it’s in your allowed categories)
        if (empty($category)) {
            continue;
        }

        // Parse amount - convert to cents (int)
        // Assuming amount in decimal with dot, e.g. "123.45"
         $amount = (float) $amount;
        

        if ($amount <= 0) {
            continue;
        }

        //todo: if duplicated row then continue after logging;

        // Description can be empty or optional
        $description = trim($description);

        // Create Expense entity
        $expense = new Expense(
            null,           // id = null for new
            $userId,
            $date,
            $category,
            $amount,
            $description
        );
        //check duplicated row

        if ($this->expenses->checkAlreadyExists($expense))
        {
            $csvIgnoredDuplicates++;
          //  var_dump($csvIgnoredDuplicates);
            continue;
        }
        //var_dump($csvIgnoredDuplicates);
        // Save expense
        $this->expenses->save($expense);

        $importedCount++;
    }

    fclose($handle);

    return [$importedCount, $csvIgnoredDuplicates];
   }

   public function getExpenseById(int $id): ?Expense
  {
    return $this->expenses->findById($id);
   }

  public function deleteExpense(int $id): void
  {
    $this->expenses->delete($id);
  }

}
