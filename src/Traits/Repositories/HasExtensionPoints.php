<?php

namespace Imagina\Icore\Traits\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasExtensionPoints
{
    /**
     * Filters to be excluded from the generic filtering loop.
     * Child repositories can override this to exclude or customize filters.
     * @var array
     */
    protected array $replaceFilters = [];

    /**
     * Sync config for model relationships.
     * Format: ['relationName' => ['method' => 'sync', 'type' => 'belongsToMany']]
     * @var array
     */
    protected array $replaceSyncModelRelations = [];

    /**
     * Default eager loading config per context (index/show/etc).
     * @var array
     *
     */
    protected array $with = [];

    /**
     * @param Builder $query
     * @param object $filter
     * @param object $params
     * @return Builder
     */
    protected function filterQuery(Builder $query, object $filter, object $params): Builder
    {
        return $query;
    }

    /**
     * @param Model $model
     * @param array $data
     * @return Model
     */
    protected function syncModelRelations(Model $model, array $data): Model
    {
        return $model;
    }


    /**
     * @param array $data
     * @return void
     */
    protected function beforeCreate(array &$data): void
    {
    }

    /**
     * @param Model $model
     * @param array $data
     * @return void
     */
    protected function afterCreate(Model &$model, array &$data): void
    {
    }

    /**
     * @param $data
     * @return void
     */
    protected function beforeUpdate(&$data): void
    {
    }

    /**
     * @param $model
     * @param $data
     * @return void
     */
    protected function afterUpdate(&$model, &$data): void
    {
    }
}
