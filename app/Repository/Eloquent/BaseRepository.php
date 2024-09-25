<?php

namespace App\Repository\Eloquent;

use App\Repository\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
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
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function allWithCount(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->orderBy('created_at','desc')->withCount($relations)->get($columns);
    }

    /**
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->orderBy('created_at','desc')->with($relations)->get($columns);
    }


    /**
     * @param array $columns
     * @param array $relations
     * @param array $wheres
     * @param bool $orderBy
     * @return Collection
     */
    public function allWhere(array $columns = ['*'], array $relations = [], array $wheres = [], bool $orderBy = false): Collection
    {
        $query = $this->model->select($columns)->with($relations)->where($wheres);
        if ($orderBy) {
            $query->orderBy('created_at','desc');
        }
        else{
            $query->orderBy('created_at','asc');
        }
        return $query->get();
    }

    /**
     * Get all trashed models.
     *
     * @return Collection
     */
    public function allTrashed(): Collection
    {
        return $this->model->onlyTrashed()->get();
    }

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
    ): ?Model {
        return $this->model->select($columns)
        ->with($relations)
        ->when($withTrashed, function ($query) {
            $query->withTrashed();
        })
        ->findOrFail($modelId)->append($appends);
    }

    /**
     * Find model by query.
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
    ): ?Collection
    {
        return $this->model->select($columns)->with($relations)->where($wheres)->get();
    }

    /**
     * Find model by query.
     *
     * @param int $modelId
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function findByWhereIn(
        string $column = 'id',
        array $whereIns = [],
        array $columns = ['*'],
        array $relations = []
    ): ?Collection
    {
        return $this->model->select($columns)->with($relations)->whereIn($column, $whereIns)->get();
    }

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
    ): ?Collection
    {
        return $this->model->select($columns)->with($relations)->where($wheres)->whereNotIn($column, $whereNotIns)->get();
    }


    /**
     * Find trashed model by id.
     *
     * @param int $modelId
     * @return Model
     */
    public function findTrashedById(int $modelId): ?Model
    {
        return $this->model->withTrashed()->findOrFail($modelId);
    }

    /**
     * Find only trashed model by id.
     *
     * @param int $modelId
     * @return Model
     */
    public function findOnlyTrashedById(int $modelId): ?Model
    {
        return $this->model->onlyTrashed()->findOrFail($modelId);
    }

    /**
     * Create a model.
     *
     * @param array $payload
     * @return Model
     */
    public function create(array $payload): ?Model
    {
        $model = $this->model->create($payload);

        return $model->fresh();
    }

    /**
     * Bulk create a model.
     *
     * @param array $payload
     * @return Model
     */
    public function bulkCreate(array $payload): bool
    {
        return $this->model->insert($payload);
    }

    /**
     * Update existing model.
     *
     * @param int $modelId
     * @param array $payload
     * @return bool
     */
    public function update(int $modelId, array $payload): bool
    {
        $model = $this->findById($modelId);

        return $model->update($payload);
    }


    /**
     * Bulk Update existing model
     *
     * @param array $payload
     * @param array $where
     * @return bool
     */
    public function bulkUpdate(array $payload, array $where = []): bool
    {
        return $this->model->where($where)->update($payload);
    }


    /**
     * Delete model by id.
     *
     * @param int $modelId
     * @return bool
     */
    public function deleteById(int $modelId): bool
    {
        return $this->findById($modelId)->delete();
    }

    /**
     * Delete model by a query.
     *
     * @param array $where
     * @return bool
     */
    public function deleteWhere(array $where = []): Collection
    {
        return $this->findByWhere($where)->each->delete();
    }

    /**
     * Delete model by ids.
     *
     * @param array $modelIds
     * @return bool
     */
    public function deleteByIds(array $modelIds = []): Collection
    {
        return $this->findByWhereIn('id',$modelIds)->each->delete();
    }

    /**
     * Delete model by WhereIn.
     *
     * @param array $modelIds
     * @return bool
     */
    public function deleteByWhereIn(string $column = 'id', array $whereIns = []): Collection
    {
        return $this->findByWhereIn($column,$whereIns)->each->delete();
    }

    /**
     * Restore model by id.
     *
     * @param int $modelId
     * @return bool
     */
    public function restoreById(int $modelId): bool
    {
        return $this->findOnlyTrashedById($modelId)->restore();
    }

    /**
     * Permanently delete model by id.
     *
     * @param int $modelId
     * @return bool
     */
    public function permanentlyDeleteById(int $modelId): bool
    {
        return $this->findTrashedById($modelId)->forceDelete();
    }
}
