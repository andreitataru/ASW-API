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

    public function userInfo(Request $request)
    {
        $user = User::where('id', $request->userId)->first();
        return $user;
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
                'number' => $request->number, 'ammountPaid' => $amountPaid, 'created_at' => \Carbon\Carbon::now(),
                'dataEntrega' => $request->dataEntrega]
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

    public function getHistoryCurrentUser()
    {
        $user = Auth::user();

        $history = \DB::table('history')->where('idCustumer', $user->id)->get();
        return $history;
    }

    public function rateVendor(Request $request) {

        $user = User::where('id', $request->vendorId)->first();
        $points = $user->points;

        if ($user->timesRated == 0){
            $user->points = $request->points;
            $user->timesRated = 1;
            $user->save();
            return 0;
        }else{

            $newPoints = ((float)$user->points * (int)$user->timesRated + (int)$request->points) / ((int)$user->timesRated + 1);
            $user->points = $newPoints;
            $user->timesRated = $user->timesRated +1;
            $user->save();

            if ($$user->timesRated > 5){
                return $$user->points;
            }else{
                return 0;
            }
            
        }
    }

    public function SendMessage(Request $request) { 

        $user = Auth::User();

         \DB::table('message')->insert(        #idReceiver  #message
                    ['idSender' => $user -> id, 
                    'idReceiver' => $request->input('idReceiver'), 
                    'message' => $request->input('message'),
                    'created_at' => \Carbon\Carbon::now()]
                ); 
    }

    public function GetMessages(Request $request) {  #idReceiver

        $user = Auth::User();

        $messages = \DB::table('message')->select('idSender','idReceiver','message','created_at')
            ->where(function($q) use($request,$user) {
                $q->where('idSender', $user -> id)
                ->Where('idReceiver', $request->input('idReceiver'));
            })
            ->orWhere(function($q2) use($request,$user) {
                $q2->where('idSender', $request->input('idReceiver'))
                ->Where('idReceiver', $user -> id);
            })
            ->orderBy('created_at', 'asc')
            ->get();
            

        return response()
        ->json($messages);  

    }

    public function GetActiveChats(Request $request) {  #idReceiver

        $user = Auth::User();
        
        $ids = array();
        
        $output = array();

        $messages = \DB::table('message')->select('idSender','idReceiver','message','created_at')
            ->where(function($q) use($request,$user) {
                $q->where('idSender', $user -> id);
            })
            ->orWhere(function($q2) use($request,$user) {
                $q2->where('idReceiver', $user -> id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

            foreach ($messages as $message) {
                if ($user->id == $message->idSender) {
                    if (($message->idReceiver != $user->id) && (in_array($message->idReceiver, $ids) == false)){
                    $ids[] = $message->idReceiver;
                    $firstName = User::findOrFail($message->idReceiver)->firstName;
                    $lastName = User::findOrFail($message->idReceiver)->lastName;
    
                    $output[] = $message->idReceiver . ',' . $firstName . ',' . $lastName;
                    }
                }
                else {
                    if (($message->idSender != $user->id) && (in_array($message->idSender, $ids) == false)){
                    $ids[] = $message->idSender;
                    $firstName = User::findOrFail($message->idSender)->firstName;
                    $lastName = User::findOrFail($message->idSender)->lastName;
    
                    $output[] = $message->idSender . ',' . $firstName . ',' . $lastName;
                    }
                }
                
            }

        return response()
        ->json($output);  
    }

}
