<?php

namespace Imagina\Icore\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait AuditTrait
{
    use SoftDeletes;

    /**
     * @param bool
     */
    public $userstamping = true;
    public $useAudit = true;

    public static function bootAuditTrait()
    {
        /**
         * add created_by and updated_by field to model fillable
         */
        static::retrieved(function ($model) {
            $model->fillable = array_merge($model->fillable, [$model->getCreatedByColumn(), $model->getUpdatedBycolumn()]);
        });

        //event before create model
        static::creating(function ($model) {
            if (! $model->isUserstamping() || is_null($model->getCreatedByColumn())) {
                return;
            }

            if (is_null($model->{$model->getCreatedByColumn()})) {
                $model->{$model->getCreatedByColumn()} = \Auth::id();
            }

            if (is_null($model->{$model->getUpdatedByColumn()}) && ! is_null($model->getUpdatedByColumn())) {
                $model->{$model->getUpdatedByColumn()} = \Auth::id();
            }
        });

        //event before update model
        static::updating(function ($model) {
            if (! $model->isUserstamping() || is_null($model->getUpdatedByColumn()) || is_null(\Auth::id())) {
                return;
            }

            if (is_null($model->{$model->getCreatedByColumn()})) {
                $model->{$model->getCreatedByColumn()} = \Auth::id();
            }

            $model->{$model->getUpdatedByColumn()} = \Auth::id();
        });

        if (static::usingSoftDeletes()) {
            static::deleting(function ($model) {
                if (! $model->isSoftDeleting() || is_null($model->getDeletedByColumn())) {
                    return;
                }

                if (is_null($model->{$model->getDeletedByColumn()})) {
                    $model->{$model->getDeletedByColumn()} = \Auth::id();
                }

                $dispatcher = $model->getEventDispatcher();

                $model->unsetEventDispatcher();

                $model->save();

                $model->setEventDispatcher($dispatcher);
            });
            static::restoring(function ($model) {
                if (! $model->isSoftDeleting() || is_null($model->getDeletedByColumn())) {
                    return;
                }

                $model->{$model->getDeletedByColumn()} = null;
            });
        }
    }

    /**
     * Has the model loaded the SoftDeletes trait.
     */
    public static function usingSoftDeletes()
    {
        static $usingSoftDeletes;

        if (is_null($usingSoftDeletes)) {
            return $usingSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(get_called_class()));
        }

        return $usingSoftDeletes;
    }

    /**
     * Get the name of the "created by" column.
     */
    public function getCreatedByColumn()
    {
        return defined('static::CREATED_BY') ? static::CREATED_BY : 'created_by';
    }

    /**
     * Get the name of the "updated by" column.
     */
    public function getUpdatedByColumn()
    {
        return defined('static::UPDATED_BY') ? static::UPDATED_BY : 'updated_by';
    }

    /**
     * Get the name of the "deleted by" column.
     */
    public function getDeletedByColumn()
    {
        return defined('static::DELETED_BY') ? static::DELETED_BY : 'deleted_by';
    }

    /**
     * Get the user that created the model.
     */
    public function creator()
    {
        return $this->belongsTo($this->getUserClass(), $this->getCreatedByColumn());
    }

    /**
     * Get the user that edited the model.
     */
    public function editor()
    {
        return $this->belongsTo($this->getUserClass(), $this->getUpdatedByColumn());
    }

    /**
     * Get the user that deleted the model.
     */
    public function destroyer()
    {
        return $this->belongsTo($this->getUserClass(), $this->getDeletedByColumn());
    }

    public function getUserClass()
    {
        return "Modules\\Iuser\\Models\\User";
    }

    /**
     * Check if we're maintaing Userstamps on the model.
     *
     * @return bool
     */
    public function isUserstamping()
    {
        return $this->useAudit;
    }

    /**
     * Check if we're maintaing Userstamps on the model.
     *
     * @return bool
     */
    public function isSoftDeleting()
    {
        return $this->useAudit;
    }

    /**
     * Get a new query builder that includes soft deleted models.
     * TODO: This is while exist flow to handled softDelete
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = parent::newQuery($excludeDeleted);

        // If soft deletes are disabled, include trashed records in the default query
        if (!$this->isSoftDeleting()) $builder->withTrashed();

        return $builder;
    }

    /**
     * Perform the actual delete query on this model instance.
     * TODO: This is while exist flow to handled softDelete
     * @return void
     */
    public function delete()
    {
        if ($this->isSoftDeleting()) return parent::delete();
        //Force Delete
        return \DB::table($this->getTable())->where($this->getKeyName(), $this->getKey())->delete();
    }
}
