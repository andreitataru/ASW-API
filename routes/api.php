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
        $api->post('getUserDishes', 'App\\Api\\V1\\Controllers\\DishController@getUserDishes');
        $api->get('getDishById', 'App\\Api\\V1\\Controllers\\DishController@getDishById');
        $api->post('updateDishImg', 'App\\Api\\V1\\Controllers\\DishController@updateDishImg');
        $api->post('changeType', 'App\\Api\\V1\\Controllers\\UserController@changeType');
        $api->post('addMoney', 'App\\Api\\V1\\Controllers\\UserController@addMoney');
        $api->post('buyDish', 'App\\Api\\V1\\Controllers\\UserController@buyDish');
        $api->get('GetAllHistory', 'App\\Api\\V1\\Controllers\\UserController@GetAllHistory');
        $api->post('rateDish', 'App\\Api\\V1\\Controllers\\DishController@rateDish'); 
        $api->post('rateVendor', 'App\\Api\\V1\\Controllers\\UserController@rateVendor');
        $api->post('userInfo', 'App\\Api\\V1\\Controllers\\UserController@userInfo');
        $api->post('editDish', 'App\\Api\\V1\\Controllers\\UserController@editDish');
        $api->post('SendMessage', 'App\\Api\\V1\\Controllers\\UserController@SendMessage');
        $api->post('GetMessages', 'App\\Api\\V1\\Controllers\\UserController@GetMessages');
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

    $api->post('server', function() {
        require_once('nusoap.php');
        $server = new \nusoap_server();

        $server->configureWSDL('TestService', false, url('server'));

        $server->register('InfoPrato',
            array('input' => 'xsd:string'),
            array('output' => 'xsd:string')
        );

        $server->register('RealizaCompra',
            array('input' => 'xsd:string'),
            array('output' => 'xsd:string')
        );

        function InfoPrato($input){
            $dish = \DB::table('dishes')->where('id', $input)->first();
        
            $array = (array) $dish;
                
            $string='';

            $numItems = count($array);
            $i = 0;
            foreach($array as $value) {
                if(++$i === $numItems) {
                    $string .= $value;
                }
                else{
                    $string .= $value . ',';
                }
            }   
            
            return $string;
        }

        function RealizaCompra($number, $dishId, $dataEntrega)  #dishId   ,  $number 
        {
            $user = Auth::user();
    
            $dish = \DB::table('dishes')->where('id', $dishId)->first();
    
            if ((int)$dish->number >= (int)$number && (float)$user->card >= ((float)$dish->price * (float)$number)){
                $newNumber = (int)$dish->number - (int)$number;
                \DB::table('dishes')
                ->where('id', $dishId)
                ->update(['number' => $newNumber]);

                $amountPaid = (float)$dish->price * (float)$number;
                $user->card = (float)$user->card - $amountPaid;
                $user->save();
                \DB::table('history')->insert(
                    ['idDish' => $dishId, 'idSeller' => $dish->userId, 'idCustumer' => $user->id, 
                    'number' => $number, 'ammountPaid' => $amountPaid, 'created_at' => \Carbon\Carbon::now(),
                    'dataEntrega' => $dataEntrega]
                    ); 
                
                $seller = User::where('id', $dish->userId)->first();
                $sellerMoney = $seller->card;
                $seller->card = $sellerMoney + $amountPaid;
                $seller->save();
    
                return 'Aceite';
            }else {
                return 'Não aceite';
            }
        }

        $rawPostData = file_get_contents("php://input");
        return \Response::make($server->service($rawPostData), 200, array('Content-Type' => 'text/xml; charset=ISO-8859-1'));
    });

});
