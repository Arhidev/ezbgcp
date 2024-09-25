<?php

namespace App\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * Get all models.
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;


    /**
     * Get all models.
     *
     * @param array $columns
     * @param array $relations
    * @param array $wheres
    * @param bool $orderBy
    * @return Collection
    */
    public function allWhere(array $columns = ['*'], array $relations = [], array $wheres = [], bool $orderBy = false): Collection;

    /**
     * Get all models.
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function allWithCount(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get all trashed models.
     *
     * @return Collection
     */
    public function allTrashed(): Collection;

    /**
     * Find model by id.
     *
     * @param int $modelId
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @param bool $withTrashed
     * @return Model
     */
    public function findById(
        int $modelId,
        array $columns = ['*'],
        array $relations = [],
        array $appends = [],
        bool $withTrashed = false
    ): ?Model;


    /**
     * Find model by query (where).
     *
     * @param int $modelId
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function findByWhere(
        array $wheres = [],
        array $columns = ['*'],
        array $relations = []
    ): ?Collection;

    /**
     * Find model by query (whereIn).
     *
     * @param string $column
     * @param array $whereIns
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function findByWhereIn(
        string $column = 'id',
        array $whereIns = [],
        array $columns = ['*'],
        array $relations = []
    ): ?Collection;

    /**
     * Find model by query by NotWhereIn.
     *
     * @param int $modelId
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function findByNotWhereIn(
        string $column = 'id',
        array $wheres = [],
        array $whereNotIns = [],
        array $columns = ['*'],
        array $relations = []
    ): ?Collection;

    /**
     * Find trashed model by id.
     *
     * @param int $modelId
     * @return Model
     */
    public function findTrashedById(int $modelId): ?Model;

    /**
     * Find only trashed model by id.
     *
     * @param int $modelId
     * @return Model
     */
    public function findOnlyTrashedById(int $modelId): ?Model;

    /**
     * Create a model.
     *
     * @param array $payload
     * @return Model
     */
    public function create(array $payload): ?Model;

    /**
     * Bulk create a model.
     *
     * @param array $payload
     * @return Model
     */
    public function bulkCreate(array $payload): bool;

    /**
     * Update existing model.
     *
     * @param int $modelId
     * @param array $payload
     * @return bool
     */
    public function update(int $modelId, array $payload): bool;


    /**
     * Bulk Update existing model
     *
     * @param array $payload
     * @param array $where
     * @return bool
     */
    public function bulkUpdate(array $payload, array $where = []): bool;


    /**
     * Delete model by id.
     *
     * @param int $modelId
     * @return bool
     */
    public function deleteById(int $modelId): bool;


    /**
     * Delete model by a query.
     *
     * @param array $where
     * @return bool
     */
    public function deleteWhere(array $where = []): Collection;

    /**
     * Delete model by ids.
     *
     * @param array $modelIds
     * @return bool
     */
    public function deleteByIds(array $modelIds = []): Collection;


    /**
     * Delete model by WhereIn.
     *
     * @param array $modelIds
     * @return bool
     */
    public function deleteByWhereIn(string $column = 'id', array $whereIns = []): Collection;

    /**
     * Restore model by id.
     *
     * @param int $modelId
     * @return bool
     */
    public function restoreById(int $modelId): bool;

    /**
     * Permanently delete model by id.
     *
     * @param int $modelId
     * @return bool
     */
    public function permanentlyDeleteById(int $modelId): bool;
}
