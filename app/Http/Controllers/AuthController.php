<?php
/**
 * Manage Authentication requests.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Http\Transformers\UserTransformer;

class AuthController extends Controller
{
    /**
     * @var Auth
     */
    protected $auth;

    /**
     * Create a new AuthController instance.
     *
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Authenticate the user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|max:255',
                'password' => 'required|min:5',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        if ($token = $this->auth->attempt($request->only('email', 'password'))) {
            return response()->json(['data' => ['token' => $token, 
                'user'=> app('fractal')->item($this->auth->user(), new UserTransformer())->getArray()]])->header('Authorization', $token);
        }

        return response()->json(['error' => ['message' => 'Incorrect Credentials', 'token' => []]], 400);
    }

    public function refresh()
    {
        if ($token = $this->auth->refreshToken()) {
            return response()->json(['data' => ['token' => $token,
                'user'=> app('fractal')->item($this->auth->user(), new UserTransformer())->getArray()]])->header('Authorization', $token);
        } else {
            return response()->json(['error' => ['message' => 'Token Error', 'token' => []]], 400);
        }
    }
}
