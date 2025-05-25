<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;


class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
{
    if ($expense->id === null) {
        // Insert new expense
        $sql = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) 
                VALUES (:user_id, :date, :category, :amount_cents, :description)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $expense->userId,
            ':date' => $expense->date->format('Y-m-d H:i:s'),
            ':category' => $expense->category,
            ':amount_cents' => $expense->amountCents,
            ':description' => $expense->description,
        ]);
        // Get the inserted ID and assign it back to the entity
        $expense->id = (int) $this->pdo->lastInsertId();
    } else {
        // Update existing expense
        $sql = 'UPDATE expenses SET user_id = :user_id, date = :date, category = :category, 
                amount_cents = :amount_cents, description = :description WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $expense->userId,
            ':date' => $expense->date->format('Y-m-d H:i:s'),
            ':category' => $expense->category,
            ':amount_cents' => $expense->amountCents,
            ':description' => $expense->description,
            ':id' => $expense->id,
        ]);
    }

        // TODO: Implement save() method.
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        return [];
    }

    

    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.
        //return 0;
        //var_dump($year);

    $sql = 'SELECT count(*) as nr FROM expenses WHERE user_id = :user_id';
    
    $params = [':user_id' => $criteria['userId']];

    $year=$criteria['year']??null;
    $month=$criteria['month']??null;
    if ($year !== null) {
        $sql .= ' AND strftime(\'%Y\', date) = :year';
        $params[':year'] = (string)$year;
    }

    if ($month !== null) {
        $sql .= ' AND strftime(\'%m\', date) = :month';
        $params[':month'] = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    }

    // Prepare and execute with params
    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['nr'];

    }

    public function listExpenditureYears(int $userId): array
    {
        // TODO: Implement listExpenditureYears() method.
        $sql="select distinct strftime('%Y', date) as year from expenses where user_id=:user_id order by year desc";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId]);
       
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        return [];
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        return [];
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        return 0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }

    //nou
    public function getExpensesByUserPaginated(
    int $userId,
    int $page = 1,
    int $pageSize = 20,
    ?int $year = null,
    ?int $month = null
): array {

    //var_dump($year);
    $offset = ($page - 1) * $pageSize;

    $sql = 'SELECT * FROM expenses WHERE user_id = :user_id';
    
    $params = [':user_id' => $userId];

    if ($year !== null) {
        $sql .= ' AND strftime(\'%Y\', date) = :year';
        $params[':year'] = (string)$year;
    }

    if ($month !== null) {
        $sql .= ' AND strftime(\'%m\', date) = :month';
        $params[':month'] = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    }

    $sql .= ' ORDER BY date DESC LIMIT :limit OFFSET :offset';

    $params[':limit'] = $pageSize;
    $params[':offset'] = $offset;

    // Prepare and execute with params
    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $expenses = [];

    foreach ($rows as $row) {
        $expenses[] = new Expense(
            (int)$row['id'],
            (int)$row['user_id'],
            new \DateTimeImmutable($row['date']),
            $row['category'],
            (int)$row['amount_cents'],
            $row['description']
        );
    }
     $totalRows=$this->countBy(['userId'=>$userId,'year'=>$year,'month'=>$month]);
    if ($pageSize!=0)
     {$totalPages=ceil($totalRows/$pageSize);}
    else
     {$totalPages=1;}
    return [$expenses,$totalPages];
}

   
    public function checkAlreadyExists(Expense $expense): bool
    {
        //var_dump($expense->date);
        
        $sql = 'SELECT count(*) as nr FROM expenses WHERE
         user_id = :user_id and date=:mydate and category=:category
         and amount_cents=:amount_cents and description=:mydescription;';
    
        // Prepare statement
    $stmt = $this->pdo->prepare($sql);

    // Bind params, including LIMIT and OFFSET as integers (PDO requires bindValue for these)
    // 3. Bind parameters
    $dateString=$expense->date->format('Y-m-d H:i:s');
    $amountString=(string)$expense->amountCents;
    $stmt->bindValue(':user_id', $expense->userId, PDO::PARAM_INT);
    $stmt->bindValue(':mydate', $dateString, PDO::PARAM_STR);
    $stmt->bindValue(':category', $expense->category, PDO::PARAM_STR);
    $stmt->bindValue(':amount_cents', $amountString, PDO::PARAM_STR);
    $stmt->bindValue(':mydescription', $expense->description, PDO::PARAM_STR);

    /*
    var_dump([
    'user_id' => $expense->userId,
    'date' => $dateString,
    'category' => $expense->category,
    'amount_cents' => $expense->amountCents,
    'description' => $expense->description,
]);*/
    // 4. Execute the query
    $stmt->execute();

    // 5. Fetch result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($result['nr']==0) return false;
      //else
      return true;
    }
   

    public function findById(int $id): ?Expense
    {
    $sql = 'SELECT * FROM expenses WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    return new Expense(
        (int)$row['id'],
        (int)$row['user_id'],
        new \DateTimeImmutable($row['date']),
        $row['category'],
        (float)$row['amount_cents'],
        $row['description']
    );
    }

}
