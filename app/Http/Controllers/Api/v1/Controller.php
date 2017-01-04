<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;

use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $statusCode = 200;
    const CODE_SUCCESS = 'Success';
    const CODE_WRONG_ARGS = 'Wrong Arguments';
    const CODE_NOT_FOUND = 'Resource Not Found';
    const CODE_INTERNAL_ERROR = 'Internal Error';
    const CODE_UNAUTHORIZED = 'Unauthorized';
    const CODE_FORBIDDEN = 'Forbidden';

    public function __construct(Request $request, Manager $fractal)
    {
        $this->fractal = $fractal;

        // Are we going to try and include data?
        if ($request->has('include')) {
            $this->fractal->parseIncludes($request->input('include'));
        }
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**************************************************
     * Success Methods
     **************************************************/

    protected function respondWithMessage($message)
    {
        return $this->respondWithArray([
            'success' => [
                'code' => self::CODE_SUCCESS,
                'http_code' => $this->statusCode,
                'message' => $message,
            ]
        ]);
    }

    protected function respondWithItem($item, $callback, $headers=[])
    {
        $resource = new Item($item, $callback);
        $rootScope = $this->fractal->createData($resource);

        return $this->respondWithArray($rootScope->toArray(), $headers);
    }

    protected function respondWithCollection($collection, $callback)
    {
        $resource = new Collection($collection, $callback);
        $rootScope = $this->fractal->createData($resource);

        return $this->respondWithArray($rootScope->toArray());
    }

    protected function respondWithPaginatedCollection($request, $paginator, $transformer)
    {
        $modelCollection = $paginator->getCollection();
        $resource = new Collection($modelCollection, $transformer);

        // append query params back onto new query
        $queryParams = array_diff_key($request->all(), array_flip(['page']));
        $paginator->appends($queryParams);
        $paginatorAdapter = new IlluminatePaginatorAdapter($paginator);

        $resource->setPaginator($paginatorAdapter);
        $rootScope = $this->fractal->createData($resource);

        return $this->respondWithArray($rootScope->toArray());
    }

    protected function paginate($collection, $perPage)
    {
        $pageStart = \Request::get('page', 1);
        $offset = ($pageStart * $perPage) - $perPage;
        $itemsForCurrentPage = $collection->splice($offset)->take($perPage);

        return new LengthAwarePaginator(
            $itemsForCurrentPage,
            $collection->count(),
            $perPage,
            Paginator::resolveCurrentPage(),
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    protected function respondWithArray(array $array, array $headers = [])
    {
        return response()->json($array, $this->statusCode, $headers);
    }

    /**************************************************
     * Error Methods
     **************************************************/

    protected function respondWithError($message, $errorCode)
    {
        if ($this->statusCode === 200) {
            trigger_error(
                "You better have a really good reason for erroring on a 200...",
                E_USER_WARNING
            );
        }

        return $this->respondWithArray([
            'error' => [
                'code' => $errorCode,
                'http_code' => $this->statusCode,
                'message' => $message,
            ]
        ]);
    }

    /**
     * Generates a Response with a 403 HTTP header and a given message.
     *
     * @return  Response
     */
    public function errorForbidden($message = 'Forbidden')
    {
        return $this->setStatusCode(403)
            ->respondWithError($message, self::CODE_FORBIDDEN);
    }
    /**
     * Generates a Response with a 500 HTTP header and a given message.
     *
     * @return  Response
     */
    public function errorInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(500)
            ->respondWithError($message, self::CODE_INTERNAL_ERROR);
    }
    /**
     * Generates a Response with a 404 HTTP header and a given message.
     *
     * @return  Response
     */
    public function errorNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)
            ->respondWithError($message, self::CODE_NOT_FOUND);
    }
    /**
     * Generates a Response with a 401 HTTP header and a given message.
     *
     * @return  Response
     */
    public function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)
            ->respondWithError($message, self::CODE_UNAUTHORIZED);
    }
    /**
     * Generates a Response with a 400 HTTP header and a given message.
     *
     * @return  Response
     */
    public function errorWrongArgs($message = 'Wrong Arguments')
    {
        return $this->setStatusCode(400)
            ->respondWithError($message, self::CODE_WRONG_ARGS);
    }
}
