<?php

namespace Imagina\Icore\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Imagina\Icore\Transformers\CoreResource;
use Illuminate\Database\Eloquent\Model;
use Imagina\Icore\Repositories\CoreRepository;
use Imagina\Icore\Traits\Controller\CoreApiControllerHelpers;
use Symfony\Component\HttpFoundation\Response;

abstract class CoreApiController
{
    use CoreApiControllerHelpers;

    public function __construct(
        protected Model          $model,
        protected CoreRepository $modelRepository)
    {
    }

    /**
     * Controller to create model
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            //Get model data
            $modelData = $request->input('attributes') ?? [];

            //Validate Request
            $this->validateWithModelRules($modelData, 'create');

            //Create model
            $model = $this->modelRepository->create($modelData);

            //Response
            $response = ['data' => CoreResource::transformData($model)];
            DB::commit(); //Commit to Data Base
        } catch (Exception $e) {
            DB::rollback(); //Rollback to Data Base
            [$status, $response] = $this->getErrorResponse($e);
        }
        //Return response
        return response()->json($response, $status ?? Response::HTTP_CREATED);
    }

    /**
     * Controller To request all model data
     *
     * @return mixed
     */
    public function index(Request $request): JsonResponse
    {
        try {
            //Get Parameters from request
            $params = $this->getParamsRequest($request);

            //Request data to Repository
            $models = $this->modelRepository->getItemsBy($params);

            //Response
            $response = ['data' => $this->modelRepository->getItemsByTransformed($models, $params)];

            if ($params->page) $response['meta'] = ['page' => $this->pageTransformer($models)];
        } catch (Exception $e) {
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }

    /**
     * Controller to request model by criteria
     *
     * @return mixed
     */
    public function show($criteria, Request $request): JsonResponse
    {
        try {
            //Get Parameters from request
            $params = $this->getParamsRequest($request);

            //Request data to Repository
            $model = $this->modelRepository->getItem($criteria, $params);

            //Throw exception if no found item
            if (!$model) {
                throw new Exception('Item not found', Response::HTTP_NOT_FOUND);
            }

            //Response
            $response = ['data' => CoreResource::transformData($model)];
        } catch (Exception $e) {
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }

    /**
     * Controller to update model by criteria
     *
     * @return mixed
     */
    public function update($criteria, Request $request): JsonResponse
    {
        DB::beginTransaction(); //DB Transaction
        try {
            //Get model data
            $modelData = $request->input('attributes') ?? [];
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //auto-insert the criteria in the data to update
            isset($params->filter->field) ? $field = $params->filter->field : $field = 'id';
            $modelData[$field] = $criteria;

            //Validate Request
            $this->validateWithModelRules($modelData, 'update');

            //Update model
            $model = $this->modelRepository->updateBy($criteria, $modelData, $params);

            //Throw exception if no found item
            if (!$model) throw new Exception('Item not found', Response::HTTP_NOT_FOUND);

            //Response
            $response = ['data' => CoreResource::transformData($model)];
            DB::commit(); //Commit to DataBase
        } catch (Exception $e) {
            DB::rollback(); //Rollback to Data Base
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }

    /**
     * Controller to delete model by criteria
     *
     * @return mixed
     */
    public function delete($criteria, Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            //Get params
            $params = $this->getParamsRequest($request);

            //Get model data
            $modelData = $request->input('attributes') ?? [];

            //Validate Request
            $this->validateWithModelRules($modelData, 'delete');

            //Delete model
            $this->modelRepository->deleteBy($criteria, $params);

            //Response
            $response = ['data' => 'Item deleted'];
            DB::commit(); //Commit to Data Base
        } catch (Exception $e) {
            DB::rollback(); //Rollback to Data Base
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }

    /**
     * Controller to delete model by criteria
     *
     * @return mixed
     */
    public function restore($criteria, Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            //Get params
            $params = $this->getParamsRequest($request);

            //Delete model
            $model = $this->modelRepository->restoreBy($criteria, $params);

            //Throw exception if no found item
            if (!$model) throw new Exception('Item not found', Response::HTTP_NOT_FOUND);

            //Response
            $response = ['data' => CoreResource::transformData($model)];
            DB::commit(); //Commit to Data Base
        } catch (Exception $e) {
            DB::rollback(); //Rollback to Data Base
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }

    /**
     * Controller to do a bulk order of a model
     *
     * @return mixed
     */
    public function bulkOrder(Request $request): JsonResponse
    {
        DB::beginTransaction(); //DB Transaction
        try {
            //Get model data
            $data = $request->input('attributes') ?? [];
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Update model
            $bulkOrderResult = $this->modelRepository->bulkOrder($data, $params);

            //Response
            $response = ['data' => CoreResource::transformData($bulkOrderResult)];
            DB::commit(); //Commit to DataBase
        } catch (Exception $e) {
            DB::rollback(); //Rollback to Data Base
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboardIndex(Request $request): JsonResponse
    {
        try {
            //Get Parameters from request
            $params = $this->getParamsRequest($request);

            //Request data to Repository
            $dashboardData = $this->modelRepository->getDashboard($params);

            //Response
            $response = ['data' => $dashboardData];
        } catch (Exception $e) {
            [$status, $response] = $this->getErrorResponse($e);
        }

        //Return response
        return response()->json($response, $status ?? Response::HTTP_OK);
    }
}
