<?php

namespace Modules\Core\Console\Installers\Scripts;

use Illuminate\Console\Command;
use Modules\Core\Console\Installers\SetupScript;

class ModuleSeeders implements SetupScript
{

  /**
   * Fire the install script
   *
   * @return mixed
   */
  public function fire(Command $command)
  {
    if ($command->option('verbose')) {
      $command->blockMessage('Seeds', 'Running the module seeds ...', 'comment');
    }

    $modules = config('asgard.core.config.CoreModules');
    foreach ($modules as $module) {
      if ($command->option('verbose')) {
        $command->call('module:seed', ['module' => $module]);

        continue;
      }
      $command->callSilent('module:seed', ['module' => $module]);
    }
  }
}
