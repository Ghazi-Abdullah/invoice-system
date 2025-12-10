<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\BaseRepository;

class ClientRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Client());
    }

    public function search($searchTerm, $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $searchTerm . '%')
                      ->orWhere('company_name', 'like', '%' . $searchTerm . '%');
            })
            ->get(['id', 'name', 'email', 'company_name', 'phone']);
    }
}
