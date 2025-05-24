<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): ?User
    {
        // TODO: check that a user with same username does not exist, create new user and persist
        $user=$this->users->findByUsername($username);
        //can return null if the user already exists!!! 

        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that
        //done
        // TODO: here is a sample code to start with
        if (!isset($user))
        {
        $user = new User(null, $username, password_hash($password,PASSWORD_DEFAULT), new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
        }
        else
        {
        return null;
        }
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: implement this for authenticating the user
        // TODO: make sur ethe user exists and the password matches
        // TODO: don't forget to store in session user data needed afterwards
        $user=$this->users->findByUsername($username);
        if (!isset($user)) return false;

        if ($user && password_verify($password, $user->passwordHash)) {
            $_SESSION['user'] = [
                 'id' => $user->id,
                 'username' => $user->username];
           return true;
        }

        return false;

    }
}
