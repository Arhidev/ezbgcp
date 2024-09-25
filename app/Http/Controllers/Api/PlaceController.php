<?php

namespace App\Http\Controllers\Api;

use App\Repository\PlaceRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PlaceController extends Controller
{
    //
    private $placeRepository;
    public function __construct(
        PlaceRepositoryInterface $placeRepository
    ) {
        $this->placeRepository = $placeRepository;
    }

    public function getPlaces(Request $request, bool $favorite = false)
    {
        // get the required user
        $user = $request->user();
        //if the user found
        if ($user) {
            $places = $this->placeRepository->allWhere(['*'], [], [['user_id', '=', $user->id], ['favorite', '=' , $favorite ? 1 : 0]]);
            if($favorite)
            {
                return response()->json(['favorite_places' => $places]);
            }
            else
            {
                return response()->json(['recent_places' => $places]);
            }
        }
        else
        {
            return response()->json(['errors' => ['User' => ['user does not exist']]], 403);
        }
    }
    //getFavoritePlaces
    public function getFavoritePlaces(Request $request)
    {
        return $this->getPlaces($request, true);
    }

    //get recent places
    public function getRecentPlaces(Request $request)
    {
        return $this->getPlaces($request, false);
    }

    public function createEdit(Request $request)
    {
        Log::info('request: createEdit' . json_encode($request->place));
        // get the user
        $user = $request->user();
        //if the user found
        if ($user) {
            //validate the request
            $this->validate($request, [
                'place' => 'required',
                'place.id' => 'integer|nullable',
                'place.name' => 'required|string',
                'place.address' => 'required|string',
                'place.latitude' => 'required|numeric',
                'place.longitude' => 'required|numeric',
                'place.type' => 'required|numeric',
                'place.favorite' => 'required|numeric',
            ], [], []);

            //append user_id to the request
            $place = $request->place;
            $place['user_id'] = $user->id;

            $update = false;
            $place_id = null;
            if(array_key_exists('id', $place) && $place['id'] != null)
            {
                $update = true;
                $place_id = $place['id'];
            }
            if($update)
            {
                $this->placeRepository->update($place_id, $place);
                $savedPlace = $this->placeRepository->findById($place_id);
            }
            else
            {
                $savedPlace = $this->placeRepository->create($place);
            }
            return response()->json(['place' => $savedPlace]);
        }
        else
        {
            return response()->json(['errors' => ['User' => ['user does not exist']]], 403);
        }
    }

    //deletePlace
    public function deletePlace(Request $request)
    {
        // get the user
        $user = $request->user();
        //if the user found
        if ($user) {
            //validate the request
            $this->validate($request, [
                'id' => 'required|integer',
            ], [], []);

            $place_id = $request->id;
            $this->placeRepository->deleteById($place_id);
            return response()->json(['success' => ['place deleted successfully']]);
        }
        else
        {
            return response()->json(['errors' => ['User' => ['user does not exist']]], 403);
        }
    }
}
