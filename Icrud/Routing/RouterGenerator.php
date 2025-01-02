<?php

namespace Modules\Core\Icrud\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;

//Controllers
use Modules\Core\Icrud\Controllers\BaseCrudController;

class RouterGenerator
{
  private $router;

  public function __construct(Router $router)
  {
    $this->router = $router;
  }

  /**
   * Generate CRUD API routes
   *
   * @param array $params [module,prefix,controller]
   */
  public function apiCrud($params)
  {
    //Get routes
    $crudRoutes = isset($params['staticEntity']) ? $this->getStaticApiRoutes($params) :
      $crudRoutes = $this->getStandardApiRoutes($params);

    //Generate routes
    $this->router->group(['prefix' => $params['prefix']], function (Router $router) use ($crudRoutes, $params) {
      foreach ($crudRoutes as $route) {
        $router->match($route->method, $route->path, $route->actions);
      }
      //Load the customRoutes
      if (isset($params['customRoutes'])) {
        foreach ($params['customRoutes'] as $route) {
          if (isset($route['method']) && isset($route['path']) && isset($route['uses'])) {
            $router->match($route['method'], $route['path'], [
              'as' => "api.{$params['module']}.{$params['prefix']}.{$route['uses']}",
              'uses' => $params['controller'] . "@" . $route['uses'],
              'middleware' => $route['middleware'] ?? ['auth:api']
            ]);
          }
        }
      }
    });
  }

  /**
   * Return routes to standar API
   *
   * @param $params
   * @return void
   */
  private function getStandardApiRoutes($params)
  {
    return [
      (object)[//Route create
        'method' => 'post',
        'path' => '/',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.create",
          'uses' => $params['controller'] . "@create",
          'middleware' => $this->getApiRouteMiddleware('create', $params)
        ]
      ],
      (object)[//Route index
        'method' => 'get',
        'path' => '/',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.index",
          'uses' => $params['controller'] . "@index",
          'middleware' => $this->getApiRouteMiddleware('index', $params)
        ]
      ],
      (object)[//Route show
        'method' => 'get',
        'path' => '/{criteria}',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.show",
          'uses' => $params['controller'] . "@show",
          'middleware' => $this->getApiRouteMiddleware('show', $params)
        ]
      ],
      (object)[//Route Update
        'method' => 'put',
        'path' => '/{criteria}',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.update",
          'uses' => $params['controller'] . "@update",
          'middleware' => $this->getApiRouteMiddleware('update', $params)
        ]
      ],
      (object)[//Route delete
        'method' => 'delete',
        'path' => '/{criteria}',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.delete",
          'uses' => $params['controller'] . "@delete",
          'middleware' => $this->getApiRouteMiddleware('delete', $params)
        ]
      ],
      (object)[//Route delete
        'method' => 'put',
        'path' => '/{criteria}/restore',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.restore",
          'uses' => $params['controller'] . "@restore",
          'middleware' => $this->getApiRouteMiddleware('restore', $params)
        ]
      ],
      (object)[//Route bulk order
        'method' => 'put',
        'path' => '/bulk/order',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.bulk-order",
          'uses' => $params['controller'] . "@bulkOrder",
          'middleware' => isset($params['middleware']['order']) ? $params['middleware']['order'] : ['auth:api']
        ]
      ],
      (object)[//Route bulk 
        'method' => 'post',
        'path' => '/bulk/',
        'actions' => [
          'as' => "api.{$params['module']}.{$params['prefix']}.bulk",
          'uses' => $params['controller'] . "@bulk",
          'middleware' => $this->getApiRouteMiddleware('bulk', $params)
        ]
      ]
    ];
  }

  /**
   * Return the static api routes to static entities
   *
   * @param $params
   * @return void
   */
  private function getStaticApiRoutes($params)
  {
    //Instance controller
    $controller = app("Modules\Core\Icrud\Controllers\BaseCrudController");

    return [
      (object)[//Route Index
        'method' => 'get',
        'path' => '/',
        'actions' => function (Request $request) use ($controller, $params) {
          //Call indexStatic method from controller
          return $controller->indexStatic($request, [
              "entityClass" => $params['staticEntity'],
              "method" => isset($params['use']['index']) ? $params['use']['index'] : 'index'
            ]
          );
        }
      ],
      (object)[//Route Show
        'method' => 'get',
        'path' => '/{criteria}',
        'actions' => function ($criteria, Request $request) use ($controller, $params) {
          //Call showStatic method from controller
          return $controller->showStatic($criteria, $request, [
              "entityClass" => $params['staticEntity'],
              "method" => isset($params['use']['show']) ? $params['use']['show'] : 'show'
            ]
          );
        }
      ]
    ];
  }

  /**
   * Return the default permissions
   *
   * @param $params
   * @return string[]
   */
  private function getApiRouteMiddleware($route, $params)
  {
    //Return the overwrite middleware
    if (isset($params['middleware'][$route])) return $params['middleware'][$route];

    //Instance the prefix to the permissions
    $prefix = "auth-can:" . ($params['permission'] ?? "{$params['module']}.{$params['prefix']}");
    //Define the permissions
    $permissions = [
      'create' => "$prefix.create",
      'index' => "$prefix.index",
      'show' => "$prefix.index",
      'update' => "$prefix.edit",
      'delete' => "$prefix.destroy",
      'restore' => "$prefix.restore",
      'bulk' => "$prefix.bulk",
    ];

    $defaultRouteMiddleware = ["auth:api"];
    if (isset($permissions[$route])) $defaultRouteMiddleware[] = $permissions[$route];
    //Return the default middleware to the route
    return $defaultRouteMiddleware;
  }
}
