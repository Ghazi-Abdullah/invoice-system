<?php

namespace App\Repository\Admin\Client;

interface ClientInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $client);
    public function destroy($client);
    public function getClientStats($client);
    public function searchClients($request);
    public function getClientInvoices($client);
}
