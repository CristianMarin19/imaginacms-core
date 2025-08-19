<?php

namespace Imagina\Icore\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Imagina\Icore\Repositories\CoreRepository;
use Imagina\Icore\Transformers\CoreResource;

use Imagina\Icore\Traits\Repositories\HasExtensionPoints;
use Imagina\Icore\Traits\Repositories\HasRelationSync;
use Imagina\Icore\Traits\Repositories\HasQueryBuilderSupport;
use Imagina\Icore\Traits\Repositories\HasEventDispatching;

use Symfony\Component\HttpFoundation\Response;

/* TODO : check media event
use Modules\helpers\Events\CreateMedia;
use Modules\helpers\Events\UpdateMedia;

use Illuminate\Database\Eloquent\SoftDeletes;*/

abstract class EloquentCoreRepository extends EloquentBaseRepository implements CoreRepository
{
    use HasExtensionPoints, HasRelationSync, HasQueryBuilderSupport, HasEventDispatching;

    /**
     * @param object|null $params
     * @param Builder|null $query
     * @return Collection|Builder|LengthAwarePaginator
     */
    public function getItemsBy(?object $params = null, ?Builder $query = null): Collection|Builder|LengthAwarePaginator
    {
        $params = $params ?? (object)[];
        $filters = (object)($params->filter ?? []);

        // Build the query
        if (!$query) {
            $query = $this->model->query();
            $query = $this->includeToQuery($query, $params, "index");
            $query = $this->applyFiltersToQuery($query, $filters, $params);
            $query = $this->orderQuery(
                $query,
                $params->order ?? null,
                $filters->noSortOrder ?? false,
                $params->orderByRaw ?? null
            );
            //Response as query
            if (isset($params->returnAsQuery) && $params->returnAsQuery) return $query;
        }

        //Get response
        $response = !empty($params->page)
            ? $query->paginate($params->take ?? 12, ['*'], null, $params->page)
            : (isset($params->take) ? $query->take($params->take)->get() : $query->get());

        //Event return model
        $this->dispatchesEvents(['eventName' => 'retrievedIndex', 'data' => [
            "requestParams" => $params,
            "response" => $response,
        ]]);

        //Response
        return $response;
    }

    /**
     * @param Collection|LengthAwarePaginator $models
     * @return array
     */
    public function getItemsByTransformed(Collection|LengthAwarePaginator $models): array
    {
        return json_decode(json_encode(CoreResource::transformData($models)));
    }

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @param Builder|null $query
     * @return Model|Builder|null
     */
    public function getItem(string|int $criteria, ?object $params = null, ?Builder $query = null): Model|Builder|null
    {
        $params = $params ?? (object)[];
        $filters = (object)($params->filter ?? []);

        if (!$query) {
            $query = $this->model->query();
            $query = $this->includeToQuery($query, $params, "show");

            $criteriaFields = (array)($filters->field ?? ['id']);
            $translatableAttrs = $this->model->translatedAttributes ?? [];
            $locale = $filters->locale ?? app()->getLocale();

            $translatableFields = array_intersect($criteriaFields, $translatableAttrs);
            $modelFields = array_diff($criteriaFields, $translatableFields);

            if (!empty($translatableFields)) {
                $query->whereHas('translations', function ($q) use ($locale, $criteria, $translatableFields) {
                    $q->where('locale', $locale)->where(function ($subQ) use ($criteria, $translatableFields) {
                        collect($translatableFields)->reduce(function ($carry, $field) use ($subQ, $criteria) {
                            return $subQ->orWhere(camelToSnake($field), $criteria);
                        });
                    });
                });
            }

            if (!empty($modelFields)) {
                $table = $this->model->getTable();
                $query->where(function ($q) use ($modelFields, $criteria, $table) {
                    collect($modelFields)->reduce(function ($carry, $field) use ($q, $criteria, $table) {
                        return $q->orWhere("$table." . camelToSnake($field), $criteria);
                    });
                });
            }

            $query = $this->applyFiltersToQuery($query, $filters, $params);
            if (!empty($params->returnAsQuery)) return $query;
        }

        $response = $query->first();
        $this->dispatchesEvents([
            'eventName' => 'retrievedShow',
            'data' => [
                "requestParams" => $params,
                "response" => $response,
                "criteria" => $criteria
            ]
        ]);
        return $response;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        //Event creating model
        $this->dispatchesEvents(['eventName' => 'creating', 'data' => $data]);

        // allow action before create
        $this->beforeCreate($data);

        //Create model
        $model = $this->model->query()->create($data);

        // Default sync model relations
        $model = $this->defaultSyncModelRelations($model, $data);

        // Custom sync model relations
        $model = $this->syncModelRelations($model, $data);

        // allow action after creation
        $this->afterCreate($model, $data);

        //Event created model
        $this->dispatchesEvents(['eventName' => 'created', 'data' => $data, 'model' => $model]);

        //Response
        return $model;
    }


    /**
     * @param string|int $criteria
     * @param array $data
     * @param object|null $params
     * @return Model|null
     */
    public function updateBy(string|int $criteria, array $data, ?object $params = null): ?Model
    {
        //Event updating model
        $this->dispatchesEvents(['eventName' => 'updating', 'data' => $data, 'criteria' => $criteria]);

        //Instance Query
        $query = $this->model->query();

        //Check field name to criteria
        if (isset($params->filter->field)) $field = $params->filter->field;

        //get model and update
        $model = $query->where($field ?? 'id', $criteria)->first();
        if (isset($model)) {
            $data['id'] = $model->id;
            $this->beforeUpdate($data);
            // Update attributes
            $nonColumnAttributes = ['medias_single', 'medias_multi'];
            $fillableData = collect($data)->except($nonColumnAttributes)->toArray();
            $model->fill($fillableData);
            // Save model if dirty
            if ($model->isDirty()) $model->save();
            // Check for dirty translations and fire the touch to save the model timestamp
            if (method_exists($model, 'translations')) {
                foreach ($model->translations as $translation) {
                    if ($translation->isDirty()) {
                        $model->touch();
                        break;
                    }
                }
            }
            // Default Sync model relations
            $model = $this->defaultSyncModelRelations($model, $data);
            // Custom Sync model relations
            $model = $this->syncModelRelations($model, $data);
            // Call function after update it, and take all changes from $data and $model
            $this->afterUpdate($model, $data);
            //Event updated model
            $this->dispatchesEvents([
                'eventName' => 'updated',
                'data' => $data,
                'criteria' => $criteria,
                'model' => $model
            ]);
        }

        //Response
        return $model;
    }

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return bool
     */
    public function deleteBy(string|int $criteria, ?object $params = null): ?Model
    {
        //Instance Query
        $query = $this->model->query();

        //Check field name to criteria
        if (isset($params->filter->field)) $field = $params->filter->field;

        //Include trashed records | SoftDeletes
        if ($this->hasSoftDeletes()) $query->withTrashed();

        //get model
        $model = $query->where($field ?? 'id', $criteria)->first();

        if (is_null($model))
            throw new \Exception('Item not found', Response::HTTP_NOT_FOUND);

        //Event deleting model
        $this->dispatchesEvents(['eventName' => 'deleting', 'criteria' => $criteria, 'model' => $model]);

        //Delete Model
        if ($model) {
            if (isset($params->filter->forceDelete) && $this->hasSoftDeletes()) $model->forceDelete();
            else $model->delete();
        }

        //Event deleted model
        $this->dispatchesEvents(['eventName' => 'deleted', 'criteria' => $criteria]);

        //Response
        return $model;
    }

    /**
     * @param string|int $criteria
     * @param object|null $params
     * @return Model
     */
    public function restoreBy(string|int $criteria, ?object $params = null): Model
    {
        //Instance Query
        $query = $this->model->query();

        //Check field name to criteria
        if (isset($params->filter->field)) $field = $params->filter->field;

        //get model | SoftDeletes
        $model = $query->where($field ?? 'id', $criteria)->withTrashed()->first();

        //Delete Model
        if ($model) $model->restore();

        //Response
        return $model;
    }

    /**
     * @param array $data
     * @param object|null $params
     * @return Collection
     */
    public function bulkOrder(array $data, ?object $params = null): Collection
    {
        //Instance the orderField
        $orderField = $params->filter->field ?? 'position';
        //loop through data to update the position according to index data
        foreach ($data as $key => $item) {
            $this->model->query()->find($item['id'])->update([$orderField => ++$key]);
        }
        //Response
        return $this->model->query()->whereIn('id', array_column($data, "id"))->get();
    }

    /**
     * @param array $data
     * @param object|null $params
     * @return array
     */
    public function bulkUpdate(array $data, ?object $params = null): array
    {
        //Instance the orderField
        $fieldName = $params->filter->field ?? 'id';
        $updated = [];
        //loop through data to update the position according to index data
        foreach ($data as $item) {
            $updated[] = $this->updateBy($item[$fieldName], $item, $params);
        }
        //Response
        return $updated;
    }

    /**
     * @param array $data
     * @return array
     */
    public function bulkCreate(array $data): array
    {
        $created = [];
        //loop through data to create the position according to index data
        foreach ($data as $item) {
            $created[] = $this->create($item);
        }
        //Response
        return $created;
    }

    /**
     * @param array $validation
     * @param array $data
     * @return Model
     */
    public function updateOrCreate(array $validation, array $data): Model
    {
        //Search the record
        $model = $this->getItemsBy((object)['filter' => (object)$validation])->first();
        $modelData = array_merge($validation, $data);
        //update Or Create the record
        if ($model) $model = $this->updateBy($model->id, $modelData);
        else $model = $this->create($modelData);
        //Response
        return $model;
    }

    /**
     * @param object|null $params
     * @return Collection
     */
    public function getDashboard(?object $params): Collection
    {
        return new Collection();
    }
}
