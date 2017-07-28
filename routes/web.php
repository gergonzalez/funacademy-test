<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return response()->json(['data' => $app->version()]);
});

$app->post('login', 'AuthController@login');
$app->post('refresh', 'AuthController@refresh');

$app->post('website-users', 'WebsiteUserController@store');
$app->post('providers', 'ProviderController@store');
$app->post('retailers', 'RetailerController@store');

$app->group(['middleware' => 'auth'], function () use ($app) {

    $app->get('users', 'UserController@index');
    $app->get('users/{id}', 'UserController@show');
    $app->post('users/activate/{id}', 'UserController@activate');

    $app->patch('website-users/{user_id}', 'WebsiteUserController@update');

    $app->patch('providers/{user_id}', 'ProviderController@update');
    $app->post('providers/{user_id}/discount', 'ProviderController@setDiscount');
    $app->post('providers/{provider_user_id}/accept-retailer/{retailer_user_id}', 'ProviderController@acceptRetailer');

    $app->patch('retailers/{user_id}', 'RetailerController@update');
    $app->post('retailers/{retailer_user_id}/add-provider/{provider_user_id}', 'RetailerController@addProvider');
    $app->delete('retailers/{retailer_user_id}/remove-provider/{provider_user_id}', 'RetailerController@removeProvider');

    $app->post('orders', 'OrderController@store');
    $app->patch('orders/{order_id}', 'OrderController@update');
    $app->delete('orders/{order_id}', 'OrderController@destroy');
});
