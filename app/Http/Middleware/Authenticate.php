<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use App\Services\Auth\Exceptions\JwtException;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Attempt to authenticate a user via the token in the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws App\Services\Auth\Exceptions\JwtException
     */
    public function authenticate(Request $request)
    {
        if (!$this->auth->hasToken()) {
            throw new JwtException('Token not provided', 1);
        }

        if (!$this->auth->authentication()) {
            throw new JwtException('User not found', 2);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $this->authenticate($request);

        return $next($request);
    }
}
