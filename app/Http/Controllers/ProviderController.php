<?php
/**
 * Manage requests to 'providers*'.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Provider;
use App\User;

class ProviderController extends Controller
{
    /**
     * Create a new ProviderController instance.
     */
    public function __construct()
    {
    }

    /**
     * Store a newly created Provider resource in db.
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
                'companyname' => 'required|max:255',
                'phone' => 'required|digits_between:9,11',
                'IBAN' => 'required|alpha_num|max:34',
                'companyDescription' => 'max:511',
                'createdAt' => 'required|date_format:Y-m-d H:i:s',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        $new_provider = Provider::create([
          'company_name' => $request->companyname,
          'phone' => $request->phone,
          'company_description' => $request->input('companyDescription', ''),
          'iban' => $request->IBAN,
        ]);

        if (isset($new_provider) && $new_provider->id) {
            $new_user = $new_provider->user()->create([
              'email' => $request->email,
              'password' => app('hash')->make($request->password),
              'created_at' => $request->createdAt,
            ]);
        } else {
            return response()->json(['error' => ['message' => 'Provider not saved']], 422);
        }

        return response()->json(['data' => array_merge($new_user->toArray(), ['provider' => $new_provider])]);

        return response()->json(['data' => $new_user->userable]);
    }

    /**
     * Update the specified Provider in db.
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
                'companyname' => 'max:255',
                'phone' => 'digits_between:9,11',
                'IBAN' => 'alpha_num|max:34',
                'companyDescription' => 'max:511',
                'createdAt' => 'date_format:Y-m-d H:i:s',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        try {
            $user = User::findOrFail($user_id);

            if (!$user->isProvider()) {
                return response()->json(['error' => ['message' => 'Customer must be a provider']], 400);
            }

            $provider = $user->userable;

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
            if ($request->has('companyname')) {
                $provider->company_name = $request->companyname;
            }
            if ($request->has('phone')) {
                $provider->phone = $request->phone;
            }
            if ($request->has('IBAN')) {
                $provider->iban = $request->IBAN;
            }
            if ($request->has('companyDescription')) {
                $provider->company_description = $request->companyDescription;
            }

            $user->save();
            $provider->save();

            return response()->json(['data' => array_merge($user->toArray(), ['provider' => $provider])]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $user_id"]], 400);
        }
    }

    /**
     * Update the accepted pivot value in the intermediate table.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptRetailer(Request $request, $provider_user_id, $retailer_user_id)
    {
        $auth_user = $request->user();

        if (!$auth_user->isAdmin()
            && (!$auth_user->isProvider() || ($auth_user->isProvider() && $auth_user->id != $provider_user_id))) {
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

            $provider->retailers()->updateExistingPivot($retailer->id, ['accepted' => true]);

            return response()->json(['data' => array_merge($provider_user->toArray(), ['retailers' => $retailer->providers()->get()])]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => 'No users for those id']], 400);
        }
    }

    /**
     * Update the discount value in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $retailer_user_id
     * @param int                      $provider_user_id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDiscount(Request $request, $user_id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $this->validate($request, [
                'discount' => 'required|digits_between:0,100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        try {
            $user = User::findOrFail($user_id);

            if (!$user->isProvider()) {
                return response()->json(['error' => ['message' => 'The Customer must be a provider']], 400);
            }

            $provider = $user->userable;
            $provider->discount = $request->discount;
            $provider->save();

            return response()->json(['data' => $provider]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $user_id"]], 400);
        }
    }
}
