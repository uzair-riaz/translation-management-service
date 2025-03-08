<?php

namespace App\Interfaces;

interface RepositoryInterface
{
    /**
     * Get all resources.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*']);

    /**
     * Get paginated resources.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * Create a new resource.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);

    /**
     * Update a resource.
     *
     * @param array $data
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(array $data, int $id);

    /**
     * Delete a resource.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id);

    /**
     * Find a resource by ID.
     *
     * @param int $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * Find a resource by specific criteria.
     *
     * @param array $criteria
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findBy(array $criteria, array $columns = ['*']);
} 