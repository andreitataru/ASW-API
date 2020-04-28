<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

        $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
        $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
        $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
    });


    $api->group(['prefix' => 'admin'], function(Router $api) {
        $api->group(['middleware' => 'admin'], function(Router $api) {

            $api->get('GetAllUsers', 'App\\Api\\V1\\Controllers\\UserController@GetAllUsers');

            $api->get('isAdmin', function() {
                return response()->json([
                    'message' => TRUE
                ]);
            });
        }); 
    });

    $api->group(['middleware' => 'api.auth'], function (Router $api) {
        // With valid token
        $api->get('avatar', 'App\\Api\\V1\\Controllers\\AvatarController@index');
        $api->post('updateAvatar', 'App\\Api\\V1\\Controllers\\UserController@updateAvatar');
        $api->post('addDish', 'App\\Api\\V1\\Controllers\\DishController@addDish');
        $api->get('getAllDishes', 'App\\Api\\V1\\Controllers\\DishController@getAllDishes');
        $api->get('getUserDishes', 'App\\Api\\V1\\Controllers\\DishController@getUserDishes');
        $api->get('getDishById', 'App\\Api\\V1\\Controllers\\DishController@getDishById');
        $api->post('updateDishImg', 'App\\Api\\V1\\Controllers\\DishController@updateDishImg');
        $api->post('changeType', 'App\\Api\\V1\\Controllers\\UserController@changeType');
        $api->post('addMoney', 'App\\Api\\V1\\Controllers\\UserController@addMoney'); #money
        $api->post('buyDish', 'App\\Api\\V1\\Controllers\\UserController@buyDish'); #dishId, #number
    });



    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        $api->get('protected', function() {
            return response()->json([
                'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
            ]);
        });

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function() {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('hello', function() {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });
});
