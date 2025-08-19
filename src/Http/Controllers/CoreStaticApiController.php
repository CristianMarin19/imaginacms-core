<?php

namespace Imagina\Icore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CoreStaticApiController
{
    /**
     * Controller to handle index call for static entity
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $entityClass = $request->route('entityClass');
            $method = $request->route('entityMethod', 'index');

            if (!class_exists($entityClass)) {
                throw new \Exception("Invalid entity class", 400);
            }

            $model = app($entityClass);

            if (!method_exists($model, $method)) {
                throw new \Exception("Invalid method '$method' on '$entityClass'", 400);
            }

            $data = $model->$method();
            $response = ['data' => $data];
        } catch (\Exception $e) {
            $status = $e->getCode();
            $response = ['messages' => [['message' => $e->getMessage(), 'type' => 'error']]];
        }

        return response()->json($response, $status ?? 200);
    }

    /**
     * Controller to handle show call for static entity
     */
    public function show($criteria, Request $request): JsonResponse
    {
        try {
            $entityClass = $request->route('entityClass');
            $method = $request->route('entityMethod', 'show');

            if (!class_exists($entityClass)) {
                throw new \Exception("Invalid entity class", 400);
            }

            $model = app($entityClass);

            if (!method_exists($model, $method)) {
                throw new \Exception("Invalid method '$method' on '$entityClass'", 400);
            }

            $item = $model->$method($criteria);

            if (!$item) throw new \Exception("Item not found", 404);

            $response = ['data' => $item];
        } catch (\Exception $e) {
            $status = $e->getCode();
            $response = ['messages' => [['message' => $e->getMessage(), 'type' => 'error']]];
        }

        return response()->json($response, $status ?? 200);
    }
}
