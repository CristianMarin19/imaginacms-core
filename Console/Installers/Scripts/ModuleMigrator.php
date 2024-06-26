<?php

namespace Modules\Core\Console\Installers\Scripts;

use Illuminate\Console\Command;
use Modules\Core\Console\Installers\SetupScript;

class ModuleMigrator implements SetupScript
{

  /**
   * @var array
   */
  protected $notMigrate = [
    'ihelpers'
  ];

  /**
   * Fire the install script
   *
   * @return mixed
   */
  public function fire(Command $command)
  {
    if ($command->option('verbose')) {
      $command->blockMessage('Migrations', 'Starting the module migrations ...', 'comment');
    }

    $modules = config('asgard.core.config.CoreModules');
    foreach ($modules as $module) {
      if (!in_array($module, $this->notMigrate)) {
        if ($command->option('verbose')) {
          $command->call('module:migrate', ['module' => $module]);

          continue;
        }
        $command->callSilent('module:migrate', ['module' => $module]);
      }
    }
  }
}
