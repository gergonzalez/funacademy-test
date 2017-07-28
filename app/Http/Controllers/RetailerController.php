<?php
/**
 * Manage requests to 'retailers*'.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Transformers\UserTransformer;
use App\Retailer;
use App\User;

class RetailerController extends Controller
{
    /**
     * Create a new RetailerController instance.
     */
    public function __construct()
    {
    }

    /**
     * Store a newly created Retailer resource in db.
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
                'retailerResponsibleName' => 'required|max:255',
                'phone' => 'digits_between:9,11',
                'provider' => 'numeric|exists:providers,id',
                'mobilePhone' => 'required|digits_between:9,11',
                'address' => 'required',
                'IBAN' => 'required|alpha_num|max:34',
                'createdAt' => 'date_format:Y-m-d H:i:s|required',
          ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        $new_retailer = Retailer::create([
            'name' => $request->name,
            'responsible_name' => $request->retailerResponsibleName,
            'phone' => $request->input('phone', ''),
            'mobile' => $request->mobilePhone,
            'address' => $request->address,
            'iban' => $request->IBAN,
        ]);

        $new_user = $new_retailer->user()->create([
            'email' => $request->email,
            'password' => app('hash')->make($request->password),
            'active' => 0,
            'created_at' => $request->createdAt,
        ]);

        if ($request->has('provider')) {
            $new_retailer->providers()->attach($request->provider);
        }

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
                'active' => 'boolean',
                'name' => 'max:255',
                'retailerResponsibleName' => 'max:255',
                'phone' => 'digits_between:9,11',
                'mobilePhone' => 'digits_between:9,11',
                'IBAN' => 'alpha_num|max:34',
                'createdAt' => 'date_format:Y-m-d H:i:s',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        try {
            $user = User::findOrFail($user_id);

            if (!$user->isRetailer()) {
                return response()->json(['error' => ['message' => 'Must be a Retailer']], 400);
            }

            $retailer = $user->userable;

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
                $provider->name = $request->name;
            }
            if ($request->has('retailerResponsibleName')) {
                $provider->responsible_name = $request->retailerResponsibleName;
            }
            if ($request->has('phone')) {
                $provider->phone = $request->phone;
            }
            if ($request->has('mobilePhone')) {
                $provider->mobile = $request->mobilePhone;
            }
            if ($request->has('IBAN')) {
                $provider->iban = $request->IBAN;
            }

            $user->save();
            $retailer->save();

            return response()->json(app('fractal')->item($new_user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $user_id"]], 400);
        }
    }

    /**
     * Add a provider to a retailer.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $retailer_user_id
     * @param int                      $provider_user_id
     *
     * @return \Illuminate\Http\Response
     */
    public function addProvider(Request $request, $retailer_user_id, $provider_user_id)
    {
        $auth_user = $request->user();

        if (!$auth_user->isAdmin()
            && (!$auth_user->isRetailer() || ($auth_user->isRetailer() && $auth_user->id != $retailer_user_id))) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $retailer_user = User::findOrFail($retailer_user_id);
            $provider_user = User::findOrFail($provider_user_id);

            if (!$retailer_user->isRetailer()) {
                return response()->json(['error' => ['message' => 'Incorrect Retailer value']], 400);
            }

            if (!$provider_user->isProvider()) {
                return response()->json(['error' => ['message' => 'Incorrect Provider value']], 400);
            }

            $retailer = $retailer_user->userable;
            $provider = $provider_user->userable;

            $retailer->providers()->syncWithoutDetaching([$provider->id]);

            return response()->json(app('fractal')->item($new_user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => 'No users for those id']], 400);
        }
    }

    /**
     * Remove a provider.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $retailer_user_id
     * @param int                      $provider_user_id
     *
     * @return \Illuminate\Http\Response
     */
    public function removeProvider(Request $request, $retailer_user_id, $provider_user_id)
    {
        $auth_user = $request->user();

        if (!$auth_user->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $retailer_user = User::findOrFail($retailer_user_id);
            $provider_user = User::findOrFail($provider_user_id);

            if (!$retailer_user->isRetailer()) {
                return response()->json(['error' => ['message' => 'Incorrect Retailer value']], 400);
            }

            if (!$provider_user->isProvider()) {
                return response()->json(['error' => ['message' => 'Incorrect Provider value']], 400);
            }

            $retailer = $retailer_user->userable;
            $provider = $provider_user->userable;

            $retailer->providers()->detach([$provider->id]);

            return response()->json(app('fractal')->item($new_user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => 'No users for those id']], 400);
        }
    }
}
