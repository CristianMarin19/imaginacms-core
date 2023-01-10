<?php

namespace Modules\Core\Providers;

use Modules\Core\Repositories\Eloquent\LaravelEloquentRepository;
use Modules\Isite\Entities\Module;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\ModulesServiceProvider;

class LaravelModulesServiceProvider extends ModulesServiceProvider
{
  /**
   * Booting the package.
   */
  public function boot()
  {
    $this->registerNamespaces();
    $this->registerModules();
  }
  
  /**
   * Register the service provider.
   */
  public function register()
  {
    $this->registerServices();
    $this->setupStubPath();
    $this->registerProviders();
    
    $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'modules');
  }
  
  /**
   * Setup stub path.
   */
  public function setupStubPath()
  {
    $path = $this->app['config']->get('modules.stubs.path') ?? __DIR__ . '/Commands/stubs';
    Stub::setBasePath($path);
    
    $this->app->booted(function ($app) {
      /** @var RepositoryInterface $moduleRepository */
      $moduleRepository = $app[RepositoryInterface::class];
      if ($moduleRepository->config('stubs.enabled') === true) {
        Stub::setBasePath($moduleRepository->config('stubs.path'));
      }
    });
  }
  
  /**
   * {@inheritdoc}
   */
  protected function registerServices()
  {
    $this->app->singleton(Contracts\RepositoryInterface::class, function ($app) {
      $path = $app['config']->get('modules.paths.modules');
      
      $repository = new LaravelEloquentRepository($app,new Module());
      
    });
    $this->app->singleton(Contracts\ActivatorInterface::class, function ($app) {
      $activator = $app['config']->get('modules.activator');
      $class = $app['config']->get('modules.activators.' . $activator)['class'];
      
      if ($class === null) {
        throw InvalidActivatorClass::missingConfig();
      }
      
      return new $class($app);
    });
    $this->app->alias(Contracts\RepositoryInterface::class, 'modules');
  }
}