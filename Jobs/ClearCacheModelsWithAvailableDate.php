<?php

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Iwebhooks\Entities\Log;

class ClearCacheModelsWithAvailableDate implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $urls;
  public $entity;

  /**
   * Execute the job.
   */
  public function handle()
  {
    $models = iconfig('config.moduleWithAvailableDate', true);
    foreach ($models as $model) {
      if (!empty($model)) {
        $hoy = date_format(now(), 'Y-m-d');
        $itemRepository = app($model['repo']);
        $params = ['filter' => [$model['field'] => ['where' => 'date', 'value' => $hoy]]];
        $items = $itemRepository->getItemsBy($params);
        foreach ($items as $item) {
          $item->initCacheClearable();
        }
      }
    }
  }
}
