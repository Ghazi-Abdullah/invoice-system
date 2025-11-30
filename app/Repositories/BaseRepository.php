<?php
// app/Repositories/BaseRepository.php
namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*'])
    {
        return $this->model->find($id, $columns);
    }

    public function findBy(string $field, $value, array $columns = ['*'])
    {
        return $this->model->where($field, $value)->first($columns);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete(int $id)
    {
        return $this->model->destroy($id);
    }

    public function with(array $relations)
    {
        return $this->model->with($relations);
    }
}
