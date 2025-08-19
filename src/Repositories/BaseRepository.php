<?php

namespace Imagina\Icore\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface CoreRepository
 */
interface BaseRepository
{

    /**
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model;

    /**
     * @return Collection
     */
    public function all(): Collection;

    /**
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * @param Model $model
     * @param array $data
     * @return Model
     */
    public function update(Model $model, array $data): Model;

    /**
     * @param Model $model
     * @return bool
     */
    public function destroy(Model $model): bool;
}
