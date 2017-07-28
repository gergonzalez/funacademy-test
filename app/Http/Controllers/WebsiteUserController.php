<?php
/**
 * Manage requests to 'website-users*'.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Transformers\UserTransformer;
use App\WebsiteUser;
use App\User;

class WebsiteUserController extends Controller
{
    /**
     * Create a new WebsiteUserController instance.
     */
    public function __construct()
    {
    }

    /**
     * Store a newly created WebsiteUser in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|unique:users|max:255',
                'password' => 'required|min:5',
                'name' => 'required|max:255',
                'mobilePhone' => 'required|digits_between:9,11',
                'createdAt' => 'required|date_format:Y-m-d H:i:s',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        $new_website_user = WebsiteUser::create([
            'name' => $request->name,
            'mobile' => $request->mobilePhone,
        ]);

        $new_user = $new_website_user->user()->create([
          'email' => $request->email,
          'password' => app('hash')->make($request->password),
          'active' => 0,
          'created_at' => $request->createdAt,
        ]);

        return response()->json(app('fractal')->item($new_user, new UserTransformer())->getArray());
    }

    /**
     * Update the specified Website User in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $user_id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $user_id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $this->validate($request, [
                'email' => "email|unique:users,email,$user_id|max:255",
                'password' => 'min:5',
                'name' => 'max:255',
                'active' => 'boolean',
                'mobilePhone' => 'digits_between:9,11',
                'createdAt' => 'date_format:Y-m-d H:i:s',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        try {
            $user = User::findOrFail($user_id);

            if (!$user->isWebsiteUser()) {
                return response()->json(['error' => ['message' => 'Customer must be a Website User']], 400);
            }

            $website_user = $user->userable;

            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('password')) {
                $user->password = $request->password;
            }
            if ($request->has('active')) {
                $user->active = $request->active;
            }
            if ($request->has('createdAt')) {
                $user->created_at = $request->createdAt;
            }
            if ($request->has('name')) {
                $website_user->name = $request->name;
            }
            if ($request->has('mobilePhone')) {
                $website_user->mobile = $request->mobilePhone;
            }

            $user->save();
            $website_user->save();

            return response()->json(app('fractal')->item($new_user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $user_id"]], 400);
        }
    }
}
