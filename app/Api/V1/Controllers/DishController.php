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

        $user = Auth::user();
        
        if ($user->type == "Vendor" || $user->type == "Both"){
            \DB::table('dishes')->insert(
            ['userId' => $id, 'name' => $request->input('name'), 'type' => $request->input('type'), 
            'ingredients' => $request->input('ingredients'), 'number' => $request->input('number'),
            'date' => $request->input('date'), 'price' => $request->input('price'),
            'img' => url($destinationPath . $imageName), 'points' => 0, 'created_at' => \Carbon\Carbon::now(), 
            'updated_at' => \Carbon\Carbon::now(), 'local' => $user->district]
            ); 
            return response()->json([
                'status' => 'Dish Added',
            ]);
            }else {
                return response()->json([
                    'status' => 'Not a Vendor/Both',
                ]);
            }
    
    }

    public function getAllDishes()
    {
        $dishes = \DB::table('dishes')->get(); 

        return response()
        ->json($dishes);  
    }

    public function getUserDishes(Request $request)
    {
        $user = Auth::user();
        $dishes = \DB::table('dishes')->where('userId', $user->id)->get();

        return response()
        ->json($dishes);  
    }

    public function getDishById(Request $request)
    {
        $dish = \DB::table('dishes')->where('id', $request->id)->get();

        return response()
        ->json($dish);  
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

    public function rateDish(Request $request) {

        $dish = \DB::table('dishes')->where('id', $request->id)->first();
        $points = $dish->points;

        if ($dish->timesRated == 0){
            \DB::table('dishes')
            ->where('id', $request->id)
            ->update(['points' => $request->points, 'timesRated' => 1]);
            return 0;
        }else{
            #( CurrentAvg * N + NewRating ) / ( N + 1)

            $newPoints = ((float)$dish->points * (int)$dish->timesRated + (int)$request->points) / ((int)$dish->timesRated + 1);

            \DB::table('dishes')
            ->where('id', $request->id)
            ->update(['points' => $newPoints, 'timesRated' => $dish->timesRated + 1]);

            if ($dish->timesRated > 5){
                return $dish->points;
            }else{
                return 0;
            }
            
        }
    }

}
