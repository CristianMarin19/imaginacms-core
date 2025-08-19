<?php

namespace Imagina\Icore\Repositories\Cache;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Imagina\Icore\Repositories\BaseRepository;

abstract class CoreCacheDecorator extends BaseCacheDecorator implements BaseRepository
{
    /**
     * @param object|null $params
     * @return Collection
     */
    public function getItemsBy(?object $params = null, ?Builder $query = null): Collection|Builder|LengthAwarePaginator
    {
        $queryParams = clone($params ?? (object)[]);
        $queryParams->returnAsQuery = true;
        $query = $this->repository->getItemsBy($queryParams);
        return $this->remember(function () use ($params, $query) {
            return $this->repository->getItemsBy($params, $query);
        }, $this->makeCacheKey(null, $query, $queryParams));
    }

    /**
     * @param Collection $models
     * @param object $params
     * @return Collection
     */
    public function getItemsByTransformed(Collection|LengthAwarePaginator $models, object $params): array
    {
        $queryParams = clone($params ?? (object)[]);
        $queryParams->returnAsQuery = true;
        $queryParams->transformed = true;
        $query = $this->repository->getItemsBy($queryParams);
        return $this->remember(function () use ($models, $params) {
            return $this->repository->getItemsByTransformed($models);
        }, $this->makeCacheKey(null, $query, $queryParams));
    }

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return Model|null
     */
    public function getItem(string|int $criteria, ?object $params = null, ?Builder $query = null): ?Model
    {
        $queryParams = clone($params ?? (object)[]);
        $queryParams->returnAsQuery = true;
        $query = $this->repository->getItem($criteria, $queryParams);
        return $this->remember(function () use ($criteria, $params) {
            return $this->repository->getItem($criteria, $params);
        }, $this->makeCacheKey(null, $query, $queryParams));
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        $this->clearCache();
        return $this->repository->create($data);
    }

    /**
     * @param string|int $criteria
     * @param array $data
     * @param object|null $params
     * @return Model|null
     */
    public function updateBy(string|int $criteria, array $data, ?object $params = null): ?Model
    {
        $this->clearCache();
        return $this->repository->updateBy($criteria, $data, $params);
    }

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return bool
     */
    public function deleteBy(string|int $criteria, ?object $params = null): ?Model
    {
        $this->clearCache();
        return $this->repository->deleteBy($criteria, $params);
    }

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return Model
     */
    public function restoreBy(string|int $criteria, ?object $params = null): Model
    {
        $this->clearCache();
        return $this->repository->restoreBy($criteria, $params);
    }

    /**
     * @param array $data
     * @param object|null $params
     * @return Collection
     */
    public function bulkOrder(array $data, ?object $params = null): Collection
    {
        $this->clearCache();
        return $this->repository->bulkOrder($data, $params);
    }

    /**
     * @param array $data
     * @param object|null $params
     * @return Collection
     */
    public function bulkUpdate(array $data, ?object $params = null): array
    {
        $this->clearCache();
        return $this->repository->bulkUpdate($data, $params);
    }

    /**
     * @param array $data
     * @return Collection
     */
    public function bulkCreate(array $data): array
    {
        $this->clearCache();
        return $this->repository->bulkCreate($data);
    }

    /**
     * @param object|null $params
     * @return Collection
     */
    public function getDashboard(?object $params): Collection
    {
        return $this->remember(function () use ($params) {
            return $this->repository->getDashboard($params);
        }, $this->makeCacheKey(null, null, $params));
    }


    /**
     * @param array $validationData
     * @param array $data
     * @return Model
     */
    public function updateOrCreate(array $validation, array $data): Model
    {
        $this->clearCache();
        return $this->repository->updateOrCreate($validation, $data);
    }
}
