<?php

namespace Imagina\Icore\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Imedia\Transformers\FileTransformer;

class CoreResource extends JsonResource
{
    /**
     * Attribute to exclude relations from transformed data
     * @var array
     */
    protected array $excludeRelations = [];


    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $response = $this->modelAttributes($request);
        $response = $this->mergeFillable($response);
        $response = $this->mergeTranslated($response);
        $response = $this->mergeRelations($response);
        //TODO: CHeck if needed put here the magic attributes again

        //Sort response
        ksort($response);

        //Response
        return $response;
    }

    /**
     * @param $request
     * @return array
     */
    public function modelAttributes($request): array
    {
        return [];
    }

    /**
     * @param array $response
     * @return array
     */
    public function mergeFillable(array $response): array
    {
        $fields = method_exists($this->resource, 'getFillables')
            ? $this->resource->getFillables()
            : [];
        $fields = array_unique(array_merge($fields, array_keys($this->resource->getAttributes())));
        $allKeys = array_diff($fields, ($this->resource->getHidden() ?? []));

        foreach ($allKeys as $field) {
            $camel = snakeToCamel($field);
            if (!isset($response[$camel])) {
                $response[$camel] = $this->when(
                    (isset($this[$field]) || is_null($this[$field])),
                    $this[$field]
                );
            }
        }

        return $response;
    }

    /**
     * @param array $response
     * @return array
     */
    public function mergeTranslated(array $response): array
    {
        if (!$this->resource->relationLoaded('translations')) return $response;
        $languages = getSupportedLocales(); // Get site languages
        $translatable = array_filter(($this->translatedAttributes ?? []), fn($val) => $val !== 'locale');

        foreach ($translatable as $field) {
            $camel = snakeToCamel($field);
            if (!array_key_exists($camel, $response)) {
                $response[$camel] = $this->when(
                    (isset($this[$field]) || is_null($this[$field])),
                    $this[$field]
                );
            }
        }

        foreach ($languages as $lang => $value) {
            foreach ($translatable as $fieldName) {
                $response[$lang][snakeToCamel($fieldName)] = $this->resource->hasTranslation($lang) ?
                    $this->resource->translate($lang)[$fieldName] : '';
            }
        }

        return $response;
    }

    /**
     * @param array $response
     * @return array
     */
    public function mergeRelations(array $response): array
    {
        $excludeRelations = array_merge(['translations'], $this->excludeRelations);

        foreach ($this->resource->getRelations() as $relationName => $relation) {
            if (
                in_array($relationName, $excludeRelations, true)
                || isset($response[$relationName])
            ) {
                continue;
            }

            // transform the relation
            $response[$relationName] = $this->transformData($relation);

            //Format files relations
            //TODO: check if this is needed, maybe put in the transformer of relation
            if (
                $relationName === 'fields'
                && method_exists($this->resource, 'formatFillableToModel')
            ) {
                $fillableData = json_decode(json_encode($response[$relationName]));
                $response = array_merge_recursive(
                    $response,
                    $this->resource->formatFillableToModel($fillableData)
                );
            }

            //Format files relations
            if (($relationName == 'files') && method_exists($this->resource, 'mediaFiles')) {
                //Add files relations
                $response["files"] = FileTransformer::collection($this->files);
                //Add media Files
                if (method_exists($this->resource, 'mediaFiles')) $response['mediaFiles'] = $this->mediaFiles();
            }

            // TODO: put again media a revisionable relations manage?
        }

        return $response;
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function transformData($data): mixed
    {
        //Transform from collections
        if (($data instanceof Collection) || ($data instanceof LengthAwarePaginator)) {
            return (isset($data->first()->transformer) && $data->first()->transformer) ?
                $data->first()->transformer::collection($data) : CoreResource::collection($data);
        } //Transform from a model
        else {
            return (isset($data->transformer) && $data->transformer) ?
                new $data->transformer($data) : new CoreResource($data);
        }
    }
}
