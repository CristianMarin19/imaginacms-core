<?php

namespace Modules\Core\Icrud\Traits;

use Modules\Core\Jobs\ClearCacheByRoutes;
use Modules\Core\Jobs\ClearCacheWithCDN;
use Modules\Core\Jobs\ClearAllResponseCache;

trait HasCacheClearable
{
  public static function bootHasCacheClearable()
  {
    static::created(function ($model) {
      $model->initCacheClearable();
    });
    static::saved(function ($model) {
      if ($model->wasRecentlyCreated) return; //Validate saved only for updated model
      $model->initCacheClearable();
    });

    static::deleting(function ($model) {
      $model->initCacheClearable(false);
    });
  }

  /**
   * Call the cache providers to clear model cache
   *
   * @return void
   */
  public function initCacheClearable()
  {
    $clearResponseCache = true;
    if (!is_null(request()->input('setting'))) {
      $settingsRequest = json_decode(request()->input('setting'));
      $clearResponseCache = $settingsRequest->noClearResponseCache ?? true;
    }
    if ($clearResponseCache) {
      if (method_exists($this, 'getCacheClearableData')) {
        ClearCacheByRoutes::dispatch($this)->onQueue('cacheByRoutes');
        ClearCacheWithCDN::dispatch($this);
        ClearAllResponseCache::dispatch(['entity' => $this]);
      }
    }
  }
}
