<?php

namespace Imagina\Icore\Traits\Controller;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

trait CoreApiControllerHelpers
{
    /**
     * @param Request $request
     * @return object
     */
    public function getParamsRequest(Request $request): object
    {
        return (object)[
            'order' => $request->input('order'),
            'page' => $request->input('page', 1),
            'take' => $request->input('take', 12),
            'filter' => (object)(json_decode($request->input('filter', '[]'), true) ?? []),
            'include' => array_filter(explode(',', $request->input('include', '')), fn($val) => $val !== ''),
            'fields' => array_filter(explode(',', $request->input('fields', '')), fn($val) => $val !== ''),
        ];
    }

    /**
     * @param Exception $e
     * @return array
     */
    public function getErrorResponse(Exception $e): array
    {

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $code = Response::HTTP_BAD_REQUEST;
        } else {
            $rawCode = $e->getCode();
            $code = is_int($rawCode) && $rawCode >= 100 && $rawCode <= 599
                ? $rawCode
                : Response::HTTP_INTERNAL_SERVER_ERROR; // 500
        }

        $payload = [
            'messages' => $e->getMessage(),
            'status'   => [
                'code'  => $code,
                'error' => Response::$statusTexts[$code] ?? 'Unknown status',
            ],
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];


        return [$code, $payload];
    }

    /**
     * @param array $data
     * @param string $action
     * @return void
     * @throws ValidationException
     */
    protected function validateWithModelRules(array $data, string $action): void
    {
        $class = $this->model->requestValidation[$action] ?? null;
        if ($class && class_exists($class)) {
            $rules = new $class($data);
            $rules->setContainer(app());

            if (method_exists($rules, 'getValidator')) {
                $validator = $rules->getValidator();
            } else {
                $validator = Validator::make($rules->all(), $rules->rules(), $rules->messages());
            }

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function pageTransformer($data): array
    {
        return [
            'total' => $data->total(),
            'lastPage' => $data->lastPage(),
            'perPage' => $data->perPage(),
            'currentPage' => $data->currentPage(),
        ];
    }
}
