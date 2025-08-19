<?php

namespace Imagina\Icore\Routes;

use Illuminate\Routing\Router;
use Illuminate\Http\Request;

//Controllers
use Imagina\Icore\Http\Controllers\CoreApiController;
use Imagina\Icore\Http\Controllers\CoreStaticApiController;

class RouterGenerator
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Generate CRUD API routes
     *
     * @param array $params [module,prefix,controller]
     */
    public function apiCrud(array $params): void
    {
        //Get routes
        $crudRoutes = isset($params['staticEntity']) ?
            $this->getStaticApiRoutes($params) : $this->getStandardApiRoutes($params);

        //Generate routes
        $this->router->group(['prefix' => $params['prefix']], function (Router $router) use ($crudRoutes, $params) {
            foreach ($crudRoutes as $route) {
                //Match the route with the method, path and actions
                $laravelRoute = $router->match($route->method, $route->path, $route->actions);
                if (isset($route->actions['defaults'])) {
                    foreach ($route->actions['defaults'] as $key => $value) {
                        $laravelRoute->defaults($key, $value);
                    }
                }
            }
            //Load the customRoutes
            if (isset($params['customRoutes'])) {
                foreach ($params['customRoutes'] as $route) {
                    if (isset($route['method']) && isset($route['path']) && isset($route['uses'])) {
                        $router->match($route['method'], $route['path'], [
                            'as' => "api.{$params['module']}.{$params['prefix']}.{$route['uses']}",
                            'uses' => $params['controller'] . '@' . $route['uses'],
                            'middleware' => $route['middleware'] ?? ['auth:api']
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Return routes to standar API
     */
    private function getStandardApiRoutes($params): array
    {
        return [
            (object)[ //Route create
                'method' => 'post',
                'path' => '/',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.create",
                    'uses' => $params['controller'] . '@create',
                    'middleware' => $this->getApiRouteMiddleware('create', $params)
                ],
            ],
            (object)[ //Route index
                'method' => 'get',
                'path' => '/',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.index",
                    'uses' => $params['controller'] . '@index',
                    'middleware' => $this->getApiRouteMiddleware('index', $params)
                ],
            ],
            (object)[ //Route show
                'method' => 'get',
                'path' => '/{criteria}',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.show",
                    'uses' => $params['controller'] . '@show',
                    'middleware' => $this->getApiRouteMiddleware('show', $params)
                ],
            ],
            (object)[ //Route index
                'method' => 'get',
                'path' => '/dashboard/index',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.dashboard.index",
                    'uses' => $params['controller'] . '@dashboardIndex',
                    'middleware' => $this->getApiRouteMiddleware('dashboard', $params)
                ],
            ],
            (object)[ //Route Update
                'method' => 'put',
                'path' => '/{criteria}',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.update",
                    'uses' => $params['controller'] . '@update',
                    'middleware' => $this->getApiRouteMiddleware('update', $params)
                ],
            ],
            (object)[ //Route delete
                'method' => 'delete',
                'path' => '/{criteria}',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.delete",
                    'uses' => $params['controller'] . '@delete',
                    'middleware' => $this->getApiRouteMiddleware('delete', $params)
                ],
            ],
            (object)[ //Route delete
                'method' => 'put',
                'path' => '/{criteria}/restore',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.restore",
                    'uses' => $params['controller'] . '@restore',
                    'middleware' => $this->getApiRouteMiddleware('restore', $params)
                ],
            ],
            (object)[ //Route bulk order
                'method' => 'put',
                'path' => '/bulk/order',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.bulk-order",
                    'uses' => $params['controller'] . '@bulkOrder',
                    'middleware' => $params['middleware']['order'] ?? ['auth:api'],
                ],
            ],
            (object)[ //Route bulk
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
     */
    private function getStaticApiRoutes($params): array
    {
        return [
            (object)[
                'method' => 'get',
                'path' => '/',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.index",
                    'uses' => CoreStaticApiController::class . '@index',
                    'middleware' => $params['middleware']['index'] ?? ['auth:api'],
                    'defaults' => [
                        'entityClass' => $params['staticEntity'],
                        'entityMethod' => $params['use']['index'] ?? 'index',
                    ]
                ]
            ],
            (object)[
                'method' => 'get',
                'path' => '/{criteria}',
                'actions' => [
                    'as' => "api.{$params['module']}.{$params['prefix']}.show",
                    'uses' => CoreStaticApiController::class . '@show',
                    'middleware' => $params['middleware']['show'] ?? ['auth:api'],
                    'defaults' => [
                        'entityClass' => $params['staticEntity'],
                        'entityMethod' => $params['use']['show'] ?? 'show',
                    ]
                ]
            ]
        ];
    }

    /**
     * Return the default permissions
     *
     * @param $route
     * @param $params
     * @return string[]
     */
    private function getApiRouteMiddleware($route, $params): array
    {

        //Return the middleware
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
            'dashboard' => "$prefix.dashboard",
            'bulk' => "$prefix.bulk",
        ];

        return (array)($permissions[$route] ?? []);
    }
}
