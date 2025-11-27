<?php

namespace App\Repository;

interface UserRepositoryInterface
{
    public function getUsers(array $queryParams = []): array;
    public function getUser(int $id): array;
    public function createUser(array $data): void;
    public function updateUser(int $id, array $data): void;
    public function deleteUser(int $id): void;
}
