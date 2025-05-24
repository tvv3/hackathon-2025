<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) { //var_dump('connected');
        }

    /**
     * @throws Exception
     */
    public function find(mixed $id): ?User
    {
        $query = 'SELECT * FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }

    public function findByUsername(string $username): ?User
    {
        // TODO: Implement findByUsername() method.
        //return null;
        $query = 'SELECT min(id) as min_id FROM users WHERE username = :username';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['username' => $username]);
        $data = $statement->fetch(PDO::FETCH_ASSOC);

       if (!$data || empty($data['min_id'])) {
            return null;
        }

       return $this->find((int)$data['min_id']);
    }

    public function save(User $user): void
    {
        // TODO: Implement save() method.

         if ($user->id === null) {
        // INSERT nou
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, :created_at)');
        $success = $stmt->execute([
            ':username' => $user->username,
            ':password_hash' => $user->passwordHash,
            ':created_at'=> $user->createdAt->format('Y-m-d H:i:s')
        ]);
    
        if ($success) {
            $user->id = (int)$this->pdo->lastInsertId();
        }
       // return $success;

       }
   }

}
