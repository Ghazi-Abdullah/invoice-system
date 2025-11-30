<?php
// app/Repositories/ClientRepository.php
namespace App\Repositories;

use App\Contracts\ClientRepositoryInterface;
use App\Models\Client;

class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }

    public function getUserClients(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->latest()
            ->paginate(15);
    }

    public function searchClients(int $userId, string $search)
    {
        return $this->model->where('user_id', $userId)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            })
            ->get();
    }
}
