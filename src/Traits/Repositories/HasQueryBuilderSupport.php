<?php

namespace Imagina\Icore\Traits\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Imagina\Icore\Support\FilterQueryBuilder;

trait HasQueryBuilderSupport
{
    /**
     * @param Builder $query
     * @param object $params
     * @param string|null $method
     * @return Builder
     */
    public function includeToQuery(Builder $query, object $params, ?string $method = null): Builder
    {
        $relations = $params->include ?? [];
        $withoutDefaultInclude = $params->filter?->withoutDefaultInclude ?? false;
        //request all categories instances in the "relations" attribute in the entity model
        if (in_array('*', $relations)) $relations = $this->model->getRelations() ?? [];
        else if (!$withoutDefaultInclude) {
            $relations = array_merge($relations, ($this->with['all'] ?? [])); // Include all default relations
            if ($method == 'show') $relations = array_merge($relations, ($this->with['show'] ?? [])); // include show default relations
            if ($method == 'index') $relations = array_merge($relations, ($this->with['index'] ?? [])); // include index default relation
        }
        //Filter valid Relations if is possible
        if (method_exists($this->model, 'filterValidRelations')) {
            $relations = $this->model->filterValidRelations($relations);
        }
        //Instance relations in query
        $query->with(array_unique($relations));
        //Response
        return $query;
    }

    /**
     * @param Builder $query
     * @param object $filters
     * @param object $params
     * @return Builder
     */
    protected function applyFiltersToQuery(Builder $query, object $filters, object $params): Builder
    {
        $modelRelations = $this->getModelRelations();
        $modelFillable = array_merge($this->model->getFillable(), ['id', 'created_at', 'updated_at', 'created_by', 'updated_by']);
        $translatableAttributes = $this->model->translatedAttributes ?? [];
        $filterKeys = array_diff(array_keys((array)$filters), $this->replaceFilters);

        foreach ($filterKeys as $filterName) {
            $filterNameSnake = camelToSnake($filterName);
            $filterValue = $filters->$filterName;

            if (in_array($filterNameSnake, $modelFillable)) {
                $query = FilterQueryBuilder::apply($query, $filterValue, $filterNameSnake);
            }

            if (in_array($filterNameSnake, $translatableAttributes)) {
                $query->whereHas('translations', function ($q) use ($filters, $filterNameSnake, $filterValue) {
                    $q->where('locale', $filters->locale ?? app()->getLocale());
                    FilterQueryBuilder::apply($q, $filterValue, $filterNameSnake);
                });
            }

            $relationPath = explode('.', $filterName);
            if (in_array($relationPath[0], $modelRelations)) {
                $query = FilterQueryBuilder::apply($query, (object)[
                    'where' => $modelRelations[$relationPath[0]]['relation'],
                    'value' => $filterValue
                ], $relationPath);
            }
        }

        if (!empty($filters->date)) {
            $query = FilterQueryBuilder::apply(
                $query,
                (object)['from' => $filters->date->from, 'to' => $filters->date->to, 'type' => 'date'],
                $filters->date->field ?? 'created_at'
            );
        }

        if (isset($filters->withTrashed)) {
            $query->withTrashed();
        }
        if (isset($filters->onlyTrashed)) {
            $query->onlyTrashed();
        }
        if (isset($filters->withoutTenancy)) {
            $query->withoutTenancy();
        }

        return $this->filterQuery($query, $filters, $params);
    }

    /**
     * @param Builder $query
     * @param object|null $order
     * @param bool $noSortOrder
     * @param string|null $orderByRaw
     * @return Builder
     */
    public function orderQuery(Builder $query, object|null $order, bool $noSortOrder, string|null $orderByRaw): Builder
    {
        // Use raw order if provided, stripping any potential HTML tags
        if (!empty($orderByRaw)) {
            return $query->orderByRaw(strip_tags($orderByRaw));
        }

        // Apply default sort_order ordering if available
        if (!$noSortOrder && in_array('sort_order', $this->model->getFillable())) {
            $query->orderByRaw('COALESCE(sort_order, 0) DESC');
        }

        $orderField = $order->field ?? 'created_at'; //Default field
        $orderWay = $order->way ?? 'desc'; //Default way

        // Determine if this is a translatable field
        $translatedAttributes = $this->model->translatedAttributes ?? [];

        if (in_array($orderField, $translatedAttributes)) {
            //TODO: is this working yet?
            $query->orderByTranslation($orderField, $orderWay);
        } else {
            $query->orderBy($orderField, $orderWay);
        }

        return $query;
    }

    /**
     * @return bool
     */
    private function hasSoftDeletes(): bool
    {
        return false;
        //TODO: check softDeletes trait
        //return in_array(SoftDeletes::class, class_uses_recursive($this->model));
    }
}
