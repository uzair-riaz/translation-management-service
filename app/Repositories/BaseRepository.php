<?php

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all resources.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*'])
    {
        return $this->model->all($columns);
    }

    /**
     * Get paginated resources.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * Create a new resource.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a resource.
     *
     * @param array $data
     * @param int $id
     * @return Model
     */
    public function update(array $data, int $id)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    /**
     * Delete a resource.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id)
    {
        return $this->model->destroy($id);
    }

    /**
     * Find a resource by ID.
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'])
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Find a resource by specific criteria.
     *
     * @param array $criteria
     * @param array $columns
     * @return Model|null
     */
    public function findBy(array $criteria, array $columns = ['*'])
    {
        return $this->model->where($criteria)->first($columns);
    }
} 