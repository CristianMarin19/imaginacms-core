<?php

namespace Imagina\Icore\Repositories\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class CustomBuilder extends EloquentBuilder
{

  /**
   * Intercepts the method WITH to only allow valid relationships to pass through
   *
   */
  public function with($relations, $callback = null)
  {
    // Use the model's filtering method
    if (!$callback instanceof Closure) {
      $relations = $this->getModel()->filterValidRelations($relations);
    }

    return parent::with($relations, $callback);
  }
}
