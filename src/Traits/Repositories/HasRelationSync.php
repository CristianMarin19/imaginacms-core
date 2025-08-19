<?php

namespace Imagina\Icore\Traits\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HasRelationSync
{
    /**
     * @param Model $model
     * @param array $data
     * @return Model
     */
    protected function defaultSyncModelRelations(Model $model, array $data): Model
    {
        foreach ($this->getModelRelations() as $relationName => $relation) {
            if (
                in_array($relationName, $this->replaceSyncModelRelations) ||
                !array_key_exists($relationName, $data)
            ) {
                continue;
            }

            $relationInstance = $model->$relationName();
            $relationType = $relation['type'] ?? null;
            $compareKeys = $relation['compareKeys'] ?? [];

            match ($relation['relation']) {
                'hasMany' => $this->handleHasManySync($model, $relationName, $relationInstance, $data[$relationName], $relationType, $compareKeys),
                'belongsToMany' => $this->handleBelongsToManySync($model, $relationName, $relationInstance, $data[$relationName], $relationType, $compareKeys),
                default => null,
            };
        }

        return $model;
    }

    /**
     * @return array
     */
    protected function getModelRelations(): array
    {
        $modelRelations = [];

        $rawRelations = $this->model->modelRelations ?? [];

        foreach ($rawRelations as $name => $value) {
            if (is_string($value)) {
                $modelRelations[$name] = ['relation' => $value];
            } elseif (is_array($value) && isset($value['relation'])) {
                $modelRelations[$name] = $value;
            }
        }

        return $modelRelations;
    }

    /**
     * @param Model $model
     * @param string $relationName
     * @param $relationInstance
     * @param array $items
     * @param string|null $type
     * @param array $compareKeys
     * @return void
     */
    protected function handleHasManySync(
        Model   $model,
        string  $relationName,
                $relationInstance,
        array   $items,
        ?string $type,
        array   $compareKeys
    ): void
    {
        if ($type === 'updateOrCreateMany') {
            $relatedRepositoryClass = $relationInstance->getRelated()->repository ?? null;
            $foreignKey = $relationInstance->getForeignKeyName();

            if (!$relatedRepositoryClass || !$foreignKey) return;

            $repo = app($relatedRepositoryClass);

            foreach ($items as $item) {
                if (!empty(array_diff($compareKeys, array_keys($item)))) continue;

                $compare = array_merge(
                    [$foreignKey => $model->id],
                    array_intersect_key($item, array_flip($compareKeys))
                );

                $repo->updateOrCreate($compare, $item);
            }
        } else {
            $relationInstance->forceDelete();
            $model->setRelation($relationName, $relationInstance->createMany($items));
        }
    }

    /**
     * @param Model $model
     * @param string $relationName
     * @param $relationInstance
     * @param array $items
     * @param string|null $type
     * @param array $compareKeys
     * @return void
     */
    protected function handleBelongsToManySync(
        Model   $model,
        string  $relationName,
                $relationInstance,
        array   $items,
        ?string $type,
        array   $compareKeys
    ): void
    {
        if ($type === 'updateOrCreateMany') {
            $pivotTable = $relationInstance->getTable();
            $foreignKey = $relationInstance->getRelatedPivotKeyName();
            $modelKey = $relationInstance->getForeignPivotKeyName();

            foreach ($items as $item) {
                if (!isset($item[$foreignKey]) || !empty(array_diff($compareKeys, array_keys($item)))) continue;

                $relatedId = $item[$foreignKey];
                unset($item[$foreignKey]);

                $lookup = array_merge(
                    [$modelKey => $model->id, $foreignKey => $relatedId],
                    array_intersect_key($item, array_flip($compareKeys))
                );

                DB::table($pivotTable)->updateOrInsert(
                    $lookup,
                    array_merge($item, ['updated_at' => now(), 'created_at' => now()])
                );
            }

        } else {
            $relationInstance->sync($items);
        }
        $model->setRelation($relationName, $model->$relationName);
    }
}
