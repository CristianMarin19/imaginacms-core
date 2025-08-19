<?php

namespace Imagina\Icore\Models;

use Illuminate\Database\Eloquent\Model;

/*
use Modules\Isite\Traits\RevisionableTrait;
use Imagina\Icore\Traits\SingleFlaggable;
use Imagina\Icore\Traits\HasUniqueFields;
use Imagina\Icore\Traits\HasCacheClearable;*/
use Imagina\Icore\Traits\AuditTrait;
use Imagina\Icore\Traits\hasEventsWithBindings;
use Imagina\Icore\Traits\HasOptionalTraits;
use Imagina\Icore\Repositories\Eloquent\CustomBuilder;

class CoreModel extends Model
{
    use HasOptionalTraits, hasEventsWithBindings, AuditTrait, hasEventsWithBindings;/*RevisionableTrait, SingleFlaggable, HasUniqueFields,
       HasCacheClearable,*/

    function getFillables(): array
    {
        return $this->fillable;
    }


    /**
     * Filter valid relations for eager loading.
     *
     */
    public function filterValidRelations($relations): array
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        return array_filter($relations, function ($relation) use ($relations) {
            if (is_string($relation)) $relation = explode('.', $relation)[0];
            return !is_string($relation) || method_exists($this, $relation) ||
                in_array($relation, static::$optionalTraitsRelations); //This depends on HasOptionalTraits trait
        });
    }
}
