<?php

namespace App\Api\V1\Controllers;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Auth;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::guard()->user());
    }

    public function GetAllUsers()
    {
        $id = Auth::id();
        $users = User::where('id', '!=', $id)->get();
        return $users;
    }

    public function changeType(Request $request)
    {
        $user = Auth::user();
        if ($request->newType == "Vendor" || $request->newType == "Client" || $request->newType == "Both"){
            $user->type = $request->newType;
            $user->save();
            return response()->json([
                'status' => 'ok',
            ]);
        }else {
            return response()->json([
                'status' => 'Error: Only Vendor/Client/Both allowed types',
            ]);
        }
    }

    public function updateAvatar(Request $request){

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:12048',
        ]);

        $user = Auth::user();

        $avatarName = 'a'.$user->id.'.'.request()->avatar->getClientOriginalExtension();

        $file = $request->file('avatar');

        $destinationPath = 'uploads';
        $file->move($destinationPath, $avatarName);
        
        if ($user->avatar != 'user.jpg'){
            unlink(public_path('uploads/'.$user->avatar));
        }

        $user->avatar = $avatarName;
        $user->save();

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function addMoney(Request $request)
    {
        $user = Auth::user();

        $user->card = (int)$user->card + (int)$request->money;
        $user->save();
        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function buyDish(Request $request)  #dishId   ,  $number 
    {
        $user = Auth::user();

        $dish = \DB::table('dishes')->where('id', $request->dishId)->first();

        if ((int)$dish->number >= (int)$request->number && (float)$user->card >= ((float)$dish->price * (float)$request->number)){
            $newNumber = (int)$dish->number - (int)$request->number;
            \DB::table('dishes')
            ->where('id', $request->dishId)
            ->update(['number' => $newNumber]);

            $amountPaid = (float)$dish->price * (float)$request->number;
            $user->card = (float)$user->card - $amountPaid;
            $user->save();
            \DB::table('history')->insert(
                ['idDish' => $request->dishId, 'idSeller' => $dish->userId, 'idCustumer' => $user->id, 
                'number' => $request->number, 'ammountPaid' => $amountPaid, 'created_at' => \Carbon\Carbon::now()]
                ); 
            
            $seller = User::where('id', $dish->userId)->first();
            $sellerMoney = $seller->card;
            $seller->card = $sellerMoney + $amountPaid;
            $seller->save();

            return response()->json([
                'status' => 'Dish Bought',
            ]);
        }else {
            return response()->json([
                'status' => 'Error',
            ]);
        }
    }

    public function GetAllHistory()
    {
        $history = \DB::table('history')->get();
        return $history;
    }


}
