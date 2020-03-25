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

    public function updateAvatar(Request $request){

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:12048',
        ]);

        $user = Auth::user();

        $avatarName = $user->id.'.'.request()->avatar->getClientOriginalExtension();

        $file = $request->file('avatar');

        Storage::disk('public')->put($avatarName, File::get($file));

        $user->avatar = $avatarName;
        $user->save();

        return response()->json([
            'status' => 'ok',
        ]);

    }

}
