<?php

namespace App\Api\V1\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DishController extends Controller
{
    public function addDish(Request $request)
    {
        $request->validate([
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:12048',
        ]);

        $imageName = date('mdYHis') . uniqid(). '.' .request()->img->getClientOriginalExtension();
        $file = $request->file('img');
        $destinationPath = 'uploads/dishes/';
        $file->move($destinationPath, $imageName);
        $id = Auth::id();

        \DB::table('dishes')->insert(
            ['userId' => $id, 'name' => $request->input('name'), 'type' => $request->input('type'), 
            'ingredients' => $request->input('ingredients'), 'number' => $request->input('number'),
            'date' => $request->input('date'), 'price' => $request->input('price'),
            'img' => url($destinationPath . $imageName), 'points' => 0, 'created_at' => \Carbon\Carbon::now()]
        ); 

        return response()
        ->json(['Success' => 'Dish added']);  
    }

    public function getAllDishes()
    {
        $dishes = \DB::table('dishes')->get(); 

        return response()
        ->json($dishes);  
    }

    public function getUserDishes(Request $request)
    {
        $dishes = \DB::table('dishes')->where('userId', $request->userId)->get();

        return response()
        ->json($dishes);  
    }
    
    public function updateDishImg(Request $request){

        $request->validate([
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:12048',
        ]);

        $dish = \DB::table('dishes')->where('id', $request->id)->first();

        $imageName = date('mdYHis') . uniqid(). '.' .request()->img->getClientOriginalExtension();
        $file = $request->file('img');
        $destinationPath = 'uploads/dishes/';
        $file->move($destinationPath, $imageName);

        \DB::table('dishes')
        ->where('id', $request->id)
        ->update(['img' => url($destinationPath . $imageName)]);

        return response()->json([
            'status' => 'ok',
        ]);

    }


}
