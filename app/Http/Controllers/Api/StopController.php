<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\StopRepositoryInterface;
use DB;
class StopController extends Controller
{
    //
    private $stopRepository;
    public function __construct(
        StopRepositoryInterface $stopRepository)
    {
        $this->stopRepository = $stopRepository;
    }

    public function index()
    {
        //get all stops
        return response()->json($this->stopRepository->all(['*'], ['routes']), 200);
    }

    public function getStop($stop_id)
    {
        //get stop by id
        return response()->json($this->stopRepository->findById($stop_id), 200);
    }

    public function createEdit(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'stop' => 'required',
            'stop.id' => 'integer|nullable',
            'stop.name' => 'required|string',
            'stop.address' => 'required|string',
            'stop.place_id' => 'required|string',
            'stop.lat' => 'required|numeric',
            'stop.lng' => 'required|numeric',
        ], [], []);

        $update = false;
        $stop_id = null;
        if(array_key_exists('id', $request->stop) && $request->stop['id'] != null)
        {
            $update = true;
            $stop_id = $request->stop['id'];
        }
        if($update)
        {
            //update the stop data
            $this->stopRepository->update($stop_id, $request->stop);
            return response()->json(['success' => ['stop updated successfully']]);
        }
        else
        {
            //create new stop
            $this->stopRepository->create($request->stop);
            return response()->json(['success' => ['stop created successfully']]);
        }
    }

    //destroy stop
    public function destroy($stop_id)
    {
        //check if the stop has routes
        $stop = $this->stopRepository->findById($stop_id, ['*'], ['routes']);
        if(count($stop->routes) > 0)
        {
            return response()->json(['message' => 'The stop has routes, you can not delete it'], 400);
        }
        //delete the stop
        $this->stopRepository->deleteById($stop_id);
        return response()->json(['success' => ['stop deleted successfully']]);
    }
}
