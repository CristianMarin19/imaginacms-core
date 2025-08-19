<?php

namespace Imagina\Icore\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;


//Default transformer
use Imagina\Icore\Transformers\CrudResource;

trait HasUniqueFields
{
    public static function bootHasUniqueFields()
    {
        static::creatingWithBindings(function ($model) {
            $model->validateUniqueFields($model->getEventBindings('creatingWithBindings'));
        });

        static::updatingWithBindings(function ($model) {
            $model->validateUniqueFields($model->getEventBindings('updatingWithBindings'), true);
        });
    }

    /**
     * Validate if model can be created or not
     *
     * @param $data
     * @param $isUpdating
     * @return void
     * @throws \Exception
     */
    public function validateUniqueFields($data, $isUpdating = false)
    {
        $uniqueFields = $this->uniqueFields ?? [];
        if (count($uniqueFields)) {
            $query = $this->query(); // Initialize the query

            // Separate unique fields into translatable and non-translatable
            $translatableAttributes = $this->translatedAttributes ?? [];
            $nonTranslatableFields = array_diff($uniqueFields, $translatableAttributes);
            $translatableFields = array_intersect($uniqueFields, $translatableAttributes);

            // Exclude current record if updating
            if ($isUpdating) $query->where('id', '<>', $data['data']["id"]);

            // Include the trashed
            if ($this->modelUsesSoftDeletes()) $query->withTrashed();

            // Add no translatable filters
            foreach ($nonTranslatableFields as $field) {
                if (isset($data['data'][$field])) $query->where($field, $data['data'][$field]);
            }

            // Add translatable filters
            if (count($translatableFields)) {
                $query->whereHas('translations', function ($q) use ($translatableFields, $data) {
                    $languages = array_keys(getSupportedLocales());
                    foreach ($translatableFields as $field) {
                        foreach ($languages as $lang) {
                            if (isset($data['data'][$lang][$field])) $q->where($field, $data['data'][$lang][$field]);
                        }
                    }
                });
            }

            // Throw with duplicated records
            $duplicatedRecords = $query->get();
            if ($duplicatedRecords->isNotEmpty()) {
                throw new \Exception(json_encode([
                    'messages' => [[
                        'message' => trans('isite::common.hasUniqueFields'),
                        'type' => 'error',
                        'timeOut' => 10000
                    ]],
                    'data' => $this->buildErrorResponse($duplicatedRecords)
                ]), 409);
            }
        }
    }

    /**
     * Check if the model uses the SoftDeletes trait.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    private function modelUsesSoftDeletes()
    {
        return in_array(SoftDeletes::class, class_uses_recursive(get_class($this)));
    }

    /**
     * Build the records response
     *
     * @param $records
     * @return array
     */
    private function buildErrorResponse($records)
    {
        $response = [];
        foreach ($records as $record) {
            array_push($response, [
                'id' => $record->id,
                'title' => $record->title ?? $record->name ?? $record->systemName == null,
                'createdAt' => $record->created_at,
                'updatedAt' => $record->updated_at,
                'deletedAt' => $record->deleted_at ?? null,
            ]);
        }
        return $response;
    }
}
