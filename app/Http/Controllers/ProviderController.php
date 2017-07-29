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
use App\Http\Transformers\UserTransformer;
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

        $newProvider = Provider::create([
          'company_name' => $request->companyname,
          'phone' => $request->phone,
          'company_description' => $request->input('companyDescription', ''),
          'iban' => $request->IBAN,
        ]);

        if (isset($newProvider) && $newProvider->id) {
            $newUser = $newProvider->user()->create([
              'email' => $request->email,
              'password' => app('hash')->make($request->password),
              'active' => 0,
              'created_at' => $request->createdAt,
            ]);
        } else {
            return response()->json(['error' => ['message' => 'Provider not saved']], 422);
        }

        return response()->json(app('fractal')->item($newUser, new UserTransformer())->getArray());
    }

    /**
     * Update the specified Provider in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $userId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $userId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $this->validate($request, [
                'email' => "email|unique:users,email,$userId|max:255",
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
            $user = User::findOrFail($userId);

            if (!$user->isProvider()) {
                return response()->json(['error' => ['message' => 'Customer must be a provider']], 400);
            }

            $provider = $user->userable;

            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('password')) {
                $user->password = app('hash')->make($request->password);
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

            return response()->json(app('fractal')->item($user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user with id $userId"]], 400);
        }
    }

    /**
     * Update the accepted pivot value in the intermediate table.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $providerUserId
     * @param int                      $retailerUserId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptRetailer(Request $request, $providerUserId, $retailerUserId)
    {
        $authUser = $request->user();

        if (!$authUser->isAdmin()
            && (!$authUser->isProvider() || ($authUser->isProvider() && $authUser->id != $providerUserId))) {
            return response()->json(['error' => 'You are not allowed to perform this action'], 401);
        }

        try {
            $retailerUser = User::findOrFail($retailerUserId);
            $providerUser = User::findOrFail($providerUserId);

            if (!$retailerUser->isRetailer()) {
                return response()->json(['error' => ['message' => 'Incorrect Retailer value']], 400);
            }

            if (!$providerUser->isProvider()) {
                return response()->json(['error' => ['message' => 'Incorrect Provider value']], 400);
            }

            $retailer = $retailerUser->userable;
            $provider = $providerUser->userable;

            if( !$provider->retailers()->where('retailer_id',$retailer->id)->count() ){
                return response()->json(['error' => ['message' => 'No relation between the provider and the retailer']], 400);
            }
            
            $provider->retailers()->updateExistingPivot($retailer->id, ['accepted' => true]);

            return response()->json(app('fractal')->item($retailer->user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No users with id $userId"]], 400);
        }
    }

    /**
     * Update the discount value in db.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $userId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDiscount(Request $request, $userId)
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
            $user = User::findOrFail($userId);

            if (!$user->isProvider()) {
                return response()->json(['error' => ['message' => 'The Customer must be a provider']], 400);
            }

            $provider = $user->userable;
            $provider->discount = $request->discount;
            $provider->save();

            return response()->json(app('fractal')->item($user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user with id $userId"]], 400);
        }
    }
}
