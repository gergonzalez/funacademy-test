<?php

namespace App\Exceptions;

use Exception;
use ErrorException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use App\Services\Auth\Exceptions\JwtException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        JwtException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $e
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->json(['error' => ['message' => 'File not found']], 404);
        } elseif ($e instanceof FatalThrowableError) {
            return response()->json(['error' => ['message' => 'Internal server error']], 500);
        } elseif ($e instanceof ErrorException) {
            return response()->json(['error' => ['message' => 'Internal server error']], 500);
        } elseif ($e instanceof QueryException) {
            return response()->json(['error' => ['message' => 'Database error']], 500);
        } elseif ($e instanceof JwtException) {
            $code = $e->getCode();

            if ($code === 1) {
                return response()->json(['error' => ['message' => 'No Token Provided']], 401);
            } elseif ($code === 2) {
                return response()->json(['error' => ['message' => 'User Not found']], 401);
            } elseif ($code === 3) {
                return response()->json(['error' => ['message' => 'Token Expired']], 401);
            } else {
                return response()->json(['error' => ['message' => 'Token Error']], 401);
            }
        }

        return parent::render($request, $e);
    }
}
