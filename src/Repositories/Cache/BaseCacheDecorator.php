<?php

namespace Imagina\Icore\Repositories\Cache;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Imagina\Icore\Repositories\BaseRepository;
use Illuminate\Cache\Repository;

abstract class BaseCacheDecorator implements BaseRepository
{
    protected BaseRepository $repository;

    protected Repository $cache;

    protected int $cacheTime;

    protected string $entityName;

    protected string $locale;

    protected array $tags;


    public function __construct()
    {
        $this->cache = app(Repository::class);
        $this->locale = app()->getLocale();
        $this->cacheTime = config('cache.time', 2592000);
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        return $this->remember(function () use ($id) {
            return $this->repository->find($id);
        });
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->remember(function () {
            return $this->repository->all();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->remember(function () use ($perPage) {
            return $this->repository->paginate($perPage);
        });
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
     * @param Model $model
     * @param array $data
     * @return Model
     */
    public function update(Model $model, array $data): Model
    {
        $this->clearCache();
        return $this->repository->update($model, $data);
    }

    /**
     * @param Model $model
     * @return bool
     */
    public function destroy(Model $model): bool
    {
        $this->clearCache();
        return $this->repository->destroy($model);
    }

    /**
     * @param array $extraTags
     * @return bool
     */
    public function clearCache(array $extraTags = []): bool
    {
        if (config('cache.default') != 'redis') return false;
        $tags = array_unique(array_filter(array_merge(($this->tags ?? []), [$this->entityName], $extraTags)));
        return $this->cache->tags($tags)->flush();
    }

    /**
     * @param callable $callback
     * @param string|null $key
     * @param int|null $time
     * @return mixed
     */
    protected function remember(callable $callback, ?string $key = null, ?int $time = null): mixed
    {
        $cacheKey = $this->makeCacheKey($key);
        $store = $this->cache;

        if (method_exists($this->cache->getStore(), 'tags')) {
            $store = $store->tags([$this->entityName, 'global']);
        }

        // If no $time is passed, just use the default from config
        $cacheTime = $time ?? $this->cacheTime;

        return $store->remember($cacheKey, $cacheTime, $callback);
    }

    /**
     * @param string|null $key
     * @param mixed|null $query
     * @param object|null $params
     * @return string
     */
    //TODO: Validate makeCacheKey
    protected function makeCacheKey(?string $key = null, mixed $query = null, ?object $params = null): string
    {
        // 1. If an explicit key is provided, just use it
        if ($key !== null) {
            return $key;
        }

        // 2. Base key with entity context
        $baseKey = sprintf(
            'imagina -locale:%s -entity:%s',
            $this->locale,
            $this->entityName
        );

        // 3. Get calling method and args for context (skip self and remember)
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? ['function' => 'unknown'];

        $keyData = [
            'function' => $caller['function'],
        ];

        // 4. Add the query if present
        if ($query !== null) {
            $keyData['query_sql'] = $query->toSql();
            $keyData['query_bindings'] = $query->getBindings();
        }

        // 5. Add params if present
        if ($params !== null) {
            $keyData['filter'] = $params->filter ?? null;
            $keyData['order'] = $params->order ?? null;
            $keyData['include'] = $params->include ?? null;
            $keyData['page'] = $params->page ?? null;
            $keyData['take'] = $params->take ?? null;
            $keyData['transformed'] = $params->transformed ?? null;
        }

        // 6. Serialize and hash to avoid long keys
        $keyHash = hash('sha256', serialize($keyData));

        return "$baseKey $keyHash";
    }
}
