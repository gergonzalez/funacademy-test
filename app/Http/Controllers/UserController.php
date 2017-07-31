<?php
/**
 * Manage requests to 'users*'.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Transformers\UserTransformer;
use App\User;

class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     */
    public function __construct()
    {
    }

    /**
     * Display the list users registered.
     * 
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        return response()->json(app('fractal')->collection(User::all(), new UserTransformer())->getArray());
    }

    /**
     * Display the specified user.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $userId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $userId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $user = User::findOrFail($userId);

            return response()->json(app('fractal')->item($user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => $e->getMessage()]], 400);
        }
    }

    /**
     * Update the activate value in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $userId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Request $request, $userId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $user = User::findOrFail($userId);
            $user->active = 1;
            $user->save();

            return response()->json(app('fractal')->item($user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => $e->getMessage()]], 400);
        }
    }
}
