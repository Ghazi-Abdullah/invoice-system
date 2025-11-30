<?php
// app/Contracts/ClientRepositoryInterface.php
namespace App\Contracts;

interface ClientRepositoryInterface extends RepositoryInterface
{
    public function getUserClients(int $userId);
    public function searchClients(int $userId, string $search);
}
