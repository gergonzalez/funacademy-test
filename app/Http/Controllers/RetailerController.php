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
                'provider' => 'numeric|exists:users,id',
                'mobilePhone' => 'required|digits_between:9,11',
                'address' => 'required',
                'IBAN' => 'required|alpha_num|max:34',
                'createdAt' => 'date_format:Y-m-d H:i:s|required',
          ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['message' => $e->getMessage(), 'data' => $e->getResponse()->original]], 422);
        }

        if ($request->has('provider')) {
            $user = User::find($request->provider);

            if( !$user->isProvider() ){
                return response()->json(['error' => ['message' => 'The given data failed to pass validation.', 
                    'data' => [ 'email' => 'The user is not a provider' ]]], 422);
            }
        }

        $newRetailer = Retailer::create([
            'name' => $request->name,
            'responsible_name' => $request->retailerResponsibleName,
            'phone' => $request->input('phone', ''),
            'mobile' => $request->mobilePhone,
            'address' => $request->address,
            'iban' => $request->IBAN,
        ]);

        $newUser = $newRetailer->user()->create([
            'email' => $request->email,
            'password' => app('hash')->make($request->password),
            'active' => 0,
            'created_at' => $request->createdAt,
        ]);

        if ( isset($user) ) {
            $newRetailer->providers()->attach($user->userable_id);
        }

        return response()->json(app('fractal')->item($newUser, new UserTransformer())->getArray());
    }

    /**
     * Update the specified Website User in db.
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
            $user = User::findOrFail($userId);

            if (!$user->isRetailer()) {
                return response()->json(['error' => ['message' => 'Must be a Retailer']], 400);
            }

            $retailer = $user->userable;

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
            if ($request->has('name')) {
                $retailer->name = $request->name;
            }
            if ($request->has('retailerResponsibleName')) {
                $retailer->responsible_name = $request->retailerResponsibleName;
            }
            if ($request->has('phone')) {
                $retailer->phone = $request->phone;
            }
            if ($request->has('mobilePhone')) {
                $retailer->mobile = $request->mobilePhone;
            }
            if ($request->has('IBAN')) {
                $retailer->iban = $request->IBAN;
            }

            $user->save();
            $retailer->save();

            return response()->json(app('fractal')->item($user, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => "No user exists for id $userId"]], 400);
        }
    }

    /**
     * Add a provider to a retailer.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $retailerUserId
     * @param int                      $providerUserId
     *
     * @return \Illuminate\Http\Response
     */
    public function addProvider(Request $request, $retailerUserId, $providerUserId)
    {
        $authUser = $request->user();

        if (!$authUser->isAdmin()
            && (!$authUser->isRetailer() || ($authUser->isRetailer() && $authUser->id != $retailerUserId))) {
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

            $retailer->providers()->syncWithoutDetaching([$provider->id]);

            return response()->json(app('fractal')->item($retailerUser, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => 'No users for those id']], 400);
        }
    }

    /**
     * Remove a provider.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $retailerUserId
     * @param int                      $providerUserId
     *
     * @return \Illuminate\Http\Response
     */
    public function removeProvider(Request $request, $retailerUserId, $providerUserId)
    {
        $authUser = $request->user();

        if (!$authUser->isAdmin()) {
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

            $retailer->providers()->detach([$provider->id]);

            return response()->json(app('fractal')->item($retailerUser, new UserTransformer())->getArray());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => ['message' => 'No users for those id']], 400);
        }
    }
}
