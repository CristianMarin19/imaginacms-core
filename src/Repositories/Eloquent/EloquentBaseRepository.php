<?php

namespace Imagina\Icore\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Imagina\Icore\Repositories\BaseRepository;

/**
 * Class EloquentCoreRepository
 */
abstract class EloquentBaseRepository implements BaseRepository
{
    /**
     * @var \Illuminate\Database\Eloquent\Model An instance of the Eloquent Model
     */
    protected Model $model;

    /**
     * @param  Model  $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->find($id);
        }
        return $this->model->find($id);
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->orderBy('created_at', 'DESC')->get();
        }
        return $this->model->orderBy('created_at', 'DESC')->get();
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->orderBy('created_at', 'DESC')->paginate($perPage);
        }
        return $this->model->orderBy('created_at', 'DESC')->paginate($perPage);
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * @param Model $model
     * @param array $data
     * @return Model
     */
    public function update(Model $model,  array $data): Model
    {
        $model->update($data);
        return $model;
    }

    /**
     * @param Model $model
     * @return bool
     */
    public function destroy(Model $model): bool
    {
        return $model->delete();
    }
}
