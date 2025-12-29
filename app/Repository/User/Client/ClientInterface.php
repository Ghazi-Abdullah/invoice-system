<?php
// app/Repository/User/Client/ClientInterface.php
namespace App\Repository\User\Client;

use App\Models\Client;

interface ClientInterface
{
    public function index();
    public function show(Client $client);
    public function store($request);
    public function update($request, Client $client);
    public function destroy(Client $client);
    public function search($searchTerm);
}
