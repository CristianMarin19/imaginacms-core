<?php

namespace Imagina\Icore\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

interface CoreRepository extends BaseRepository
{
    /**
     * @param object|null $params
     * @return Collection|Builder
     */
    public function getItemsBy(?object $params, ?Builder $query = null): Collection|Builder|LengthAwarePaginator;

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return Model|Builder|null
     */
    public function getItem(string|int $criteria, ?object $params, ?Builder $query = null): Model|Builder|null;

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * @param string|int $criteria
     * @param array $data
     * @param object|null $params
     * @return Model|null
     */
    public function updateBy(string|int $criteria, array $data, ?object $params): ?Model;

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return bool
     */
    public function deleteBy(string|int $criteria, ?object $params): ?Model;

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return Model|null
     */
    public function restoreBy(string|int $criteria, ?object $params): ?Model;

    /**
     * @param array $data
     * @param object|null $params
     * @return Collection
     */
    public function bulkOrder(array $data, ?object $params): Collection;

    /**
     * @param array $data
     * @param object|null $params
     * @return Collection|null
     */
    public function bulkUpdate(array $data, ?object $params): array;

    /**
     * @param array $data
     * @return Collection
     */
    public function bulkCreate(array $data): array;

    /**
     * @param array $validation
     * @param array $data
     * @return Model
     */
    public function updateOrCreate(array $validation, array $data): Model;

    /**
     * @param object|null $params
     * @return Collection
     */
    public function getDashboard(?object $params): Collection;
}
