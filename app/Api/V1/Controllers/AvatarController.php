<?php

namespace App\Api\V1\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @resource Avatar
 *
 * Registo de um Construcao
 */

class AvatarController extends Controller
{
    use Helpers;

    /**
     * Display a listing of all inspections.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $avatar = $currentUser->avatar();
        \Log::debug($avatar);

        if (!$avatar) {
            throw new NotFoundHttpException;
        }

        return Response::JSON(array('image' =>  $avatar));
    }

}