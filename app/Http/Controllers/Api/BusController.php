<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\BusRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use DB;
class BusController extends Controller
{
    //
    private $busRepository;
    private $driverRepository;
    public function __construct(
        UserRepositoryInterface $driverRepository,
        BusRepositoryInterface $busRepository)
    {
        $this->busRepository = $busRepository;
        $this->driverRepository = $driverRepository;
    }

    public function index()
    {
        //get all buses
        return response()->json($this->busRepository->all(['*'], ['driver']), 200);
    }

    public function getBus($bus_id)
    {
        //get bus by id
        return response()->json($this->busRepository->findById($bus_id), 200);
    }

    public function createEdit(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'bus' => 'required',
            'bus.id' => 'integer|nullable',
            'bus.license' => 'required|string',
            'bus.capacity' => 'required|integer',
        ], [], []);

        $update = false;
        $bus_id = null;
        if(array_key_exists('id', $request->bus) && $request->bus['id'] != null)
        {
            //update
            $update = true;
            $bus_id = $request->bus['id'];
        }
        if($update)
        {
            //update the bus data
            $this->busRepository->update($bus_id, $request->bus);
            return response()->json(['success' => ['bus updated successfully']]);
        }
        else
        {
            //create new bus
            $this->busRepository->create($request->bus);
            return response()->json(['success' => ['bus created successfully']]);
        }
    }

    public function destroy($bus_id)
    {
        //delete bus by id
        $this->busRepository->deleteById($bus_id);
        return response()->json(['success' => ['bus deleted successfully']]);
    }

    public function assignDriver(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'driver_id' => 'required|integer',
            'bus_id' => 'required|integer',
        ], [], []);

        //check if driver is already assigned to another bus
        $driverBus = $this->busRepository->findByWhere(['driver_id' => $request->driver_id], ['*']);
        if(!$driverBus->isEmpty())
        {
            return response()->json(['error' => ['driver is already assigned to another bus']], 400);
        }
        //assign driver to bus
        $this->busRepository->update($request->bus_id,
        [
            'driver_id' => $request->driver_id
        ]);
        return response()->json(['success' => ['bus driver assigned successfully']]);
    }

    //get Available Drivers that are not assigned to any bus
    public function getAvailableDrivers()
    {
        //get all available drivers
        $drivers = $this->getAvailableDriversQuery();
        return response()->json($drivers, 200);
    }

    private function getAvailableDriversQuery()
    {
        //get all driver ids in bus table
        $existingBusDrivers = $this->busRepository->all(['driver_id'])->pluck('driver_id')->toArray();
        //remove null values
        $existingBusDrivers = array_filter($existingBusDrivers);
        //get all drivers that are not assigned to any bus
        $drivers = $this->driverRepository->findByNotWhereIn('id', [['role', '=', 2]], $existingBusDrivers, ['*']);
        return $drivers;
    }

    //un-assign driver from bus
    public function unassignDriver(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'bus_id' => 'required|integer',
        ], [], []);

        //un-assign driver from bus
        $this->busRepository->update($request->bus_id,
        [
            'driver_id' => null
        ]);
        return response()->json(['success' => ['bus driver unassigned successfully']]);
    }
}
