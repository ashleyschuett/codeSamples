<?php namespace App\Http\Controllers;

use App\Varietal;

use App\Wine;
use Illuminate\Http\Request;

class FilterController extends Controller {
    /***
      params $restaurant_id, Request $request
      return avaliable filters for specific restaurant
    */
    public function AvailableFilters($restaurant_id, Request $request){

        $varietals = $this->availableVarietals($restaurant_id);

        $regions = $this->availableRegions($restaurant_id);

        $recommended = $this->avaliableRecommendations($restaurant_id);

        $available_filters = [
            'type' =>  $this->flatten($varietals, 'type_color'),
            'varietal' => $this->flatten($varietals, 'type_name'),
            'region' => $this->flatten($regions, 'broad_region'),
            'recommended' => $recommended
        ];

        return response()->json(
            ['filters' => $available_filters],
            200
        );

    }

    /***
      @params int $restaurant_id
      return wines varietals for specific restaurant
    */
    private function availableVarietals($restaurant_id){
        return Varietal::select('type_name', 'type_color')
            ->whereHas('wines', function($q) use ($restaurant_id){
                $q->forRestaurant($restaurant_id);
            })
            ->get();
    }

    /***
      @params $restaurant_id
      return largest regions for wines
    */
    private function availableRegions($restaurant_id){
        return Wine::broadestRegion()
            ->forRestaurant($restaurant_id)
            ->get();
    }

    /***
      @params int $restaurant_id
      return wines varietals for specific restaurant
    */
    private function avaliableRecommendations($restaurant_id){
        $recommended = [];
        if(Wine::forRestaurant($restaurant_id)->where('pairing',1)->first()) $recommended[] = 'pairing';
        if(Wine::forRestaurant($restaurant_id)->where('rated',1)->first()) $recommended[] = 'rated';
        if(Wine::forRestaurant($restaurant_id)->where('library',1)->first()) $recommended[] = 'library';
        if(Wine::forRestaurant($restaurant_id)->where('specials',1)->first()) $recommended[] = 'specials';
        return $recommended;
    }

    /***
      @params array $arrayOfWines, string $key
      return return flat array
    */
    private function flatten($array, $key){
        $results = [];

        foreach($array as $item){
            if(!in_array($item[$key], $results)) array_push($results, $item[$key]);
        }


        return $results;
    }

}

