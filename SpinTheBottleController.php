<?php namespace App\Http\Controllers;

use App\Wine;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class SpinTheBottleController extends Controller{

    protected $wine;

  	public function __construct(Wine $wine)
  	{

  		$this->wine = $wine;
  	}
    
    /**
     * Show the profile for the given user.
     *
     * @param  Request  $request
     * @return Response
     * Response contains the choosen wine
     * 
     */
    public function SpinTheBottleWine(Request $request)
    {

        if(!$this->wine->findByLocation($request->all())->passes() &&
            !$this->wine->findByRestaurant($request->all())->passes())
            return response()->json(array(
                'error' => 'Please choose a restaurant, or surrounding area'),
                400);

        if(!Input::has('rank'))
            return response()->json(array(
                'error' => 'Please send an average'),
                400);

        $rank = round(Input::get('rank'), -1, PHP_ROUND_HALF_DOWN);


        /* Get matching wine from restaurant */
        if(Input::has('restaurant_id')) {
            $restaurant_ids = [Input::get('restaurant_id')];
            $getWines = function($rank) use ($restaurant_ids){
                return $this->getWineByRestaurant($restaurant_ids, $rank);
            };
        }
        /* Get matching wine nearby */
        else{
            $distance = Input::get('distance', 60);
            $lat = Input::get('lat');
            $lng = Input::get('lng');
            $getWines = function($rank) use ($lat, $lng, $distance){
                return $this->getWineByArea($lat, $lng, $distance, $rank);
            };
        }

        $original_rank = $rank;
        $opposite = false;
        do{
            $rank = $this->getRank($original_rank, $rank, $opposite);
            $wines = $getWines($rank);

            if(($original_rank <= 50 && $rank == 90) || ($original_rank > 50 && $rank == 0)) $opposite = true;
        }while(count($wines) <= 0);

        $wine = $this->chooseWine($wines);

        return response()->json(array(
                "wine" => $wine),
                200);

    }

    private function getRank($original, $rank, $opposite){
        if($opposite){
            if($rank == 0 || $rank == 90)$rank = $original;
            if($original > 50 ) $rank = $rank + 10;
            else if($rank <= 50) $rank = $rank - 10;
        } else {
            if($original == 90 || $original > 50) $rank = $rank - 10;
            else if($original == 0 || $original <= 50) $rank = $rank + 10;
        }


        return $rank;
    }

    private function chooseWine($wines){
        return $wines[Mt_rand(0, count($wines)-1)];
    }

    private function getWineByRestaurant($restaurant_id, $rank)
    {
        $wines = Wine::forSpectrum($rank)
                ->forRestaurant($restaurant_id)
                ->with('vineyard', 'varietal', 'restaurant')
                ->get();

        return $wines;
    }

    private function getWineByArea($lat, $lng, $distance, $rank)
    {
        $wines = Wine::forSpectrum($rank)
                    ->byLocation($lat, $lng, $distance)
                    ->with('vineyard', 'varietal', 'restaurant')
                    ->get();

        return $wines;

    }

}

