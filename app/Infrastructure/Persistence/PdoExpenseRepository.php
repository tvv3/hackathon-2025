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
            ':date' => $expense->date->format('Y-m-d'),
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
            ':date' => $expense->date->format('Y-m-d'),
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
        return 0;
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
        return [];
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
    int $page=1,
    int $pageSize=20,
    ?int $year = null,
    ?int $month = null
): array {
    $offset = ($page - 1) * $pageSize;

    $sql = 'SELECT * FROM expenses WHERE user_id = :user_id';
    $params = ['user_id' => $userId];

    if ($year !== null) {
        $sql .= ' AND strftime(\'%Y\', date) = :year';
        $params['year'] = (string)$year;
    }

    if ($month !== null) {
        // Month formatted as zero-padded 2-digit string: '01', '02', ..., '12'
        $sql .= ' AND strftime(\'%m\', date) = :month';
        $params['month'] = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    }

    $sql .= ' ORDER BY date DESC LIMIT :limit OFFSET :offset';

    // Prepare statement
    $stmt = $this->pdo->prepare($sql);

    // Bind params, including LIMIT and OFFSET as integers (PDO requires bindValue for these)
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    if ($year !== null) {
        $stmt->bindValue(':year', (string)$year, PDO::PARAM_STR);
    }

    if ($month !== null) {
        $stmt->bindValue(':month', str_pad((string)$month, 2, '0', STR_PAD_LEFT), PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

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

    return $expenses;
   }


   

}
