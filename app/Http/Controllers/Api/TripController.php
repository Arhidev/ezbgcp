<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\RouteRepositoryInterface;
use App\Repository\SuspendedTripRepositoryInterface;
use App\Repository\TripDetailRepositoryInterface;
use App\Repository\TripRepositoryInterface;
use App\Repository\PlannedTripRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\RouteStopRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\TripSearchResultRepositoryInterface;
use App\Repository\UserPaymentRepositoryInterface;
use App\Repository\ComplaintRepositoryInterface;
use App\Repository\SettingRepositoryInterface;
use App\Repository\NotificationRepositoryInterface;
use App\Repository\CouponRepositoryInterface;
use App\Repository\CouponCustomerRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\DriverUtils;
use App\Traits\TripUtils;
use App\Models\RouteStop;
use App\Models\RouteStopDirection;
use App\Models\Route;
use App\Models\Setting;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use App\Models\TripDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use \stdClass;
use DB;
use Validator;
use App\Traits\UserUtils;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
class TripController extends Controller
{
    use DriverUtils;
    use TripUtils;
    use UserUtils;
    //
    private $tripRepository;
    private $plannedTripRepository;
    private $routeRepository;
    private $tripDetailRepository;
    private $suspendedTripRepository;
    private $userRepository;
    private $routeStopRepository;
    private $reservationRepository;
    private $tripSearchResultRepository;
    private $userPaymentRepository;
    private $complaintRepository;
    private $settingRepository;
    private $messaging;
    private $notificationRepository;
    private $couponRepository;
    private $couponCustomerRepository;

    public function __construct(
        TripRepositoryInterface $tripRepository,
        PlannedTripRepositoryInterface $plannedTripRepository,
        SuspendedTripRepositoryInterface $suspendedTripRepository,
        TripDetailRepositoryInterface $tripDetailRepository,
        RouteRepositoryInterface $routeRepository,
        UserRepositoryInterface $userRepository,
        RouteStopRepositoryInterface $routeStopRepository,
        ReservationRepositoryInterface $reservationRepository,
        TripSearchResultRepositoryInterface $tripSearchResultRepository,
        UserPaymentRepositoryInterface $userPaymentRepository,
        ComplaintRepositoryInterface $complaintRepository,
        SettingRepositoryInterface $settingRepository,
        NotificationRepositoryInterface $notificationRepository,
        CouponRepositoryInterface $couponRepository,
        CouponCustomerRepositoryInterface $couponCustomerRepository,
        Messaging $messaging
    ) {
        $this->tripRepository = $tripRepository;
        $this->plannedTripRepository = $plannedTripRepository;
        $this->tripDetailRepository = $tripDetailRepository;
        $this->routeRepository = $routeRepository;
        $this->suspendedTripRepository = $suspendedTripRepository;
        $this->userRepository = $userRepository;
        $this->routeStopRepository = $routeStopRepository;
        $this->reservationRepository = $reservationRepository;
        $this->tripSearchResultRepository = $tripSearchResultRepository;
        $this->userPaymentRepository = $userPaymentRepository;
        $this->complaintRepository = $complaintRepository;
        $this->settingRepository = $settingRepository;
        $this->notificationRepository = $notificationRepository;
        $this->couponRepository = $couponRepository;
        $this->couponCustomerRepository = $couponCustomerRepository;
        $this->messaging = $messaging;
    }

    public function index()
    {
        $activeTrips = $this->tripRepository->allWhere(['*'], ['route', 'driver'], [['status_id', '=', 1]]);
        $trashedTrips = $this->tripRepository->allWhere(['*'], ['route', 'driver'], [['status_id', '=', 3]]);
        $suspensions = $this->suspendedTripRepository->allWhere(['*'], ['trip', 'trip.route']);

        foreach ($activeTrips as $activeTrip) {
            $plannedTrips = $this->plannedTripRepository->findByWhere(['trip_id' => $activeTrip->id], ['*'], ['plannedTripDetail']);

            if(count($plannedTrips) > 0)
            {
                $stopTimes = [];
                //loop planned trips, get statistics about planned time and actual time of planned trip details
                foreach ($plannedTrips as $plannedTrip) {
                    $planned_trip_details = $plannedTrip->plannedTripDetail;
                    foreach ($planned_trip_details as $planned_trip_detail)
                    {
                        if(!array_key_exists($planned_trip_detail->stop_id, $stopTimes))
                        {
                            $stopTimes[$planned_trip_detail->stop_id] = [
                                'planned_time' => $planned_trip_detail->planned_timestamp,
                                // 'actual_time' => [],
                                'avg_diff_pre' => 0,
                                'avg_diff_post' => 0,
                                'sum_pre' => 0,
                                'sum_post' => 0,
                                'count_pre' => 0,
                                'count_post' => 0,
                                'stop_name' => $planned_trip_detail->stop->name,
                            ];
                        }

                        // $stopTimes[$planned_trip_detail->stop_id]['planned_time'][] = $planned_trip_detail->planned_timestamp;
                        // $stopTimes[$planned_trip_detail->stop_id]['actual_time'][] = $planned_trip_detail->actual_timestamp;
                        if($planned_trip_detail->actual_timestamp != null)
                        {
                            //get time difference between planned and actual
                            $planned_time = new Carbon($planned_trip_detail->planned_timestamp);
                            $actual_time = new Carbon($planned_trip_detail->actual_timestamp);
                            $diff = $actual_time->diffInMinutes($planned_time, false);
                            // Log::info('diff ' . $diff . ' for stop ' . $planned_trip_detail->stop->name);
                            if($diff > 0)
                            {
                                $stopTimes[$planned_trip_detail->stop_id]['sum_pre'] += $diff;
                                $stopTimes[$planned_trip_detail->stop_id]['count_pre']++;
                            }
                            else
                            {
                                $stopTimes[$planned_trip_detail->stop_id]['sum_post'] += (-1*$diff);
                                $stopTimes[$planned_trip_detail->stop_id]['count_post']++;
                            }

                            if($stopTimes[$planned_trip_detail->stop_id]['count_pre']>0)
                            {
                                $stopTimes[$planned_trip_detail->stop_id]['avg_diff_pre'] = $stopTimes[$planned_trip_detail->stop_id]['sum_pre'] / $stopTimes[$planned_trip_detail->stop_id]['count_pre'];
                            }

                            if($stopTimes[$planned_trip_detail->stop_id]['count_post'] > 0)
                            {
                                $stopTimes[$planned_trip_detail->stop_id]['avg_diff_post'] = $stopTimes[$planned_trip_detail->stop_id]['sum_post'] / $stopTimes[$planned_trip_detail->stop_id]['count_post'];
                            }

                            //approximate the time
                            $stopTimes[$planned_trip_detail->stop_id]['avg_diff_pre'] = round($stopTimes[$planned_trip_detail->stop_id]['avg_diff_pre']);

                            $stopTimes[$planned_trip_detail->stop_id]['avg_diff_post'] = round($stopTimes[$planned_trip_detail->stop_id]['avg_diff_post']);
                        }
                    }

                }
                $activeTrip->stopTimes = $stopTimes;
            }
        }

        return response()->json(
            [
                'activeTrips' => $activeTrips,
                'suspendedTrips' => $suspensions,
                'trashedTrips' => $trashedTrips
            ],
            200
        );
    }

    public function getTrip($trip_id)
    {
        //get trip by id
        return response()->json($this->tripRepository->findById($trip_id, ['*'], ['route', 'driver', 'tripDetails', 'tripDetails.stop']), 200);
    }


    public function checkAssignedDriverTrip($trip)
    {
        return $trip->driver;
    }



    //getPlannedTrips
    public function getPlannedTrips(Request $request)
    {
        //get all planned trips
        $plannedTrips = $this->plannedTripRepository->allWhere(['*'], ['trip', 'trip.route', 'driver', 'bus', 'reservations']);

        $upcomingTrips = [];
        $runningTrips = [];
        $completedTrips = [];

        foreach ($plannedTrips as $plannedTrip) {
            $plannedTrip->reservations_count = count($plannedTrip->reservations);
            $plannedStartTime = new Carbon($plannedTrip->plannedTripDetail[0]->planned_timestamp);
            $plannedTrip->planned_start_date_time = $plannedTrip->planned_date . ' ' . $plannedStartTime->hour . ':' . $plannedStartTime->minute . ':' . $plannedStartTime->second;

            //end time
            $plannedEndTime = new Carbon($plannedTrip->plannedTripDetail[count($plannedTrip->plannedTripDetail) - 1]->planned_timestamp);
            $plannedTrip->planned_end_date_time = $plannedTrip->planned_date . ' ' . $plannedEndTime->hour . ':' . $plannedEndTime->minute . ':' . $plannedEndTime->second;

            if ($plannedTrip->started_at == null) {
                //trip is not started yet
                array_push($upcomingTrips, $plannedTrip);
            }
            else if ($plannedTrip->ended_at == null) {
                //trip is running
                array_push($runningTrips, $plannedTrip);
            } else {
                //trip is completed
                array_push($completedTrips, $plannedTrip);
            }
        }

        //order by planned_start_date_time
        usort($upcomingTrips, function ($a, $b) {
            return $b->planned_start_date_time <=> $a->planned_start_date_time;
        });

        usort($completedTrips, function ($a, $b) {
            return $b->planned_start_date_time <=> $a->planned_start_date_time;
        });

        usort($runningTrips, function ($a, $b) {
            return $b->planned_start_date_time <=> $a->planned_start_date_time;
        });


        return response()->json(['active' => $upcomingTrips, 'completed' => $completedTrips, 'running' => $runningTrips], 200);
    }


    public function getTripsInPeriod(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'trip_id' => 'required|integer',
            'start' => 'required|date',
            'end' => 'required|date',
        ], [], []);

        $trip_id = $request->trip_id;

        $trip = $this->tripRepository->findById($trip_id, ['*'], ['route', 'driver', 'suspensions', 'tripDetails', 'tripDetails.stop']);

        $start = new Carbon($request->start);

        $end = new Carbon($request->end);
        $end->setTime(23, 59, 59);
        $end->add(1, 'months');

        list($startCal, $events) = $this->getAllEvents($trip, $start, $end);

        return response()->json(['events' => $events, 'startCal' => $startCal]);
    }

    public function getTripSuspensions(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'suspension_id' => 'required|integer',
            'trip_id' => 'required|integer',
            'start' => 'required|date',
            'end' => 'required|date',
        ], [], []);

        $suspension_id = $request->suspension_id;
        $trip_id = $request->trip_id;
        $trip = $this->tripRepository->findById($trip_id, ['*'], ['route', 'driver', 'suspensions', 'tripDetails', 'tripDetails.stop']);

        $start = new Carbon($request->start);
        $end = new Carbon($request->end);
        $end->setTime(23, 59, 59);
        $end->add(1, 'months');

        list($startCal, $events) = $this->getAllEvents($trip, $start, $end, $suspension_id);

        $suspension = $this->suspendedTripRepository->findById($suspension_id);
        return response()->json(['events' => $events, 'startCal' => $suspension->date]);
    }

    public function createEdit(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'trip' => 'required',
            'action' => 'required|string|in:create,edit,duplicate',
            'trip.id' => 'integer|nullable',
            'trip.route_id' => 'required|integer',
            'trip.repetition_period' => 'required|integer',
            'trip.stop_to_stop_avg_time' => 'required|integer',
            'trip.first_stop_time' => 'required|date_format:H:i',
            'trip.effective_date' => 'required|date',
            'trip.inter_time' => 'required|array',
            'trip.inter_time.*' => 'required|numeric|gte:0',
        ], [], []);

        //check action
        $action = $request->action;
        if ($action == 'edit') {
            if(!$request->trip['id'])
            {
                return response()->json(['errors' => ['Error' => 'Trip id is required']], 422);
            }
        }

        DB::beginTransaction();
        try {
            $route_id = $request->trip['route_id'];
            $route = $this->routeRepository->findById($route_id, ['*'], ['stops']);
            //check if the number of stops in the route is equal to the number of inter times
            if (count($request->trip['inter_time']) != count($route->stops)) {
                return response()->json(['errors' => ['Error' => 'Timetable does not match the number of stops in the selected route']], 422);
            }

            $newTripData = [
                'route_id' => $route_id,
                'first_stop_time' => $request->trip['first_stop_time'],
                'status_id' => 1,
                'repetition_period' => $request->trip['repetition_period'],
                'effective_date' => $request->trip['effective_date'],
                'stop_to_stop_avg_time' => $request->trip['stop_to_stop_avg_time'],
            ];

            if($action == 'edit')
            {
                $my_trip_id = $request->trip['id'];
                $trip = $this->tripRepository->findById($request->trip['id']);
                $newTripData['channel'] = $trip->channel;
                //update trip
                $this->tripRepository->update($trip->id, $newTripData);
                $trip->tripDetails()->delete();
            }
            else if($action == 'create' || $action == 'duplicate')
            {
                //create new trip
                $newTripData['channel'] = uniqid();
                $trip = $this->tripRepository->create($newTripData);
            }
            $tripID = $trip->id;

            $lastStopTime = 0;
            for ($i = 0; $i < count($request->trip['inter_time']); $i++) {
                $t = $request->trip['inter_time'][$i];
                if ($i == 0)
                    $lastStopTime = $request->trip['first_stop_time'];
                $lastStopTime = strtotime('+' . $t . 'minutes', strtotime($lastStopTime));
                $lastStopTime = date('H:i:s', $lastStopTime);
                $stop_id = $route->stops[$i]->id;
                $tripDetail = [
                    'stop_id' => $stop_id,
                    'planned_timestamp' => $lastStopTime,
                    'inter_time' => $t,
                    'trip_id' => $tripID
                ];
                $this->tripDetailRepository->create($tripDetail);
            }

            //update last_stop_time
            $trip = $this->tripRepository->findById($tripID, ['*'], ['tripDetails']);
            $trip->last_stop_time = $lastStopTime;
            $trip->save();

            Log::info('Action for trip ' . $trip->id . ' is ' . $action . ' and its data is ' . $trip . ' and its details are ' . $trip->tripDetails);
            //save
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage() . ', ' . $e->getFile() . ', ' . $e->getLine()], 422);
        }

        return response()->json(['success' => ['trip created successfully']]);
    }


    public function trashRestore(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'trip_id' => 'required|integer',
        ], [], []);

        $trip_id = $request->trip_id;
        $trip = $this->tripRepository->findById($trip_id);
        $trip->status_id = $trip->status_id != 1 ? 1 : 3;
        //$trip->suspend_date = $trip->status_id == 1 ? null : date('Y-m-d');
        $this->tripRepository->update($trip_id, $trip->toArray());
        return response()->json(['success' => ['trip updated successfully']]);
    }

    public function removeSuspension($suspension_id)
    {
        $this->suspendedTripRepository->deleteById($suspension_id);
    }

    public function suspend(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'trip_id' => 'required|integer',
            'date' => 'date',
            'repetition_period' => 'required|integer|gte:0',
        ], [], []);

        $date = new Carbon($request->date);
        $trip_id = $request->trip_id;
        $trip = $this->tripRepository->findById($trip_id, ['*'], ['driver', 'suspensions']);
        $effective_date = new Carbon($trip->effective_date);

        if ($date < $effective_date) {
            return response()->json(['error' => ['Invalid date']], 422);
        }

        $suspension_id_db = $this->checkSuspendedTrip($trip, $date);
        if ($suspension_id_db) {
            //trip is already suspended
            return response()->json(['error' => ['Trip is already suspended']], 422);
        } else {
            //trip is active, now suspend it
            //check first if it is assigned to a driver
            // $tripDriver = $this->checkAssignedDriverTrip($trip);
            // if ($tripDriver) {
            //     return response()->json(['error' => ['You can not suspend this trip as it is assigned to a driver']]);
            // }
            $suspendednewTripData = [
                'trip_id' => $trip_id,
                'repetition_period' => $request->repetition_period,
                'date' => $date,
            ];
            $suspendedTrip = $this->suspendedTripRepository->create($suspendednewTripData);
            //return suspension_id
            return response()->json(['success' => ['Trip suspended successfully'], 'suspension_id' => $suspendedTrip->id]);
        }
    }

    //assign driver to a trip
    public function assignDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|integer',
            'driver_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            //pass validator errors as errors object for ajax response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $trip = $this->tripRepository->findById($request->trip_id, ['*'], ['driver', 'route']);
        if (!$trip) {
            return response()->json(['error' => ['Trip not found']], 422);
        }
        $driver = $this->userRepository->findById($request->driver_id, ['*'], ['trips', 'bus']);
        if (!$driver) {
            return response()->json(['error' => ['Driver not found']], 422);
        }
        //$tripDriver = $this->checkAssignedDriverTrip($trip);
        //check if the driver is available for the trip

        // $tripIntersect = $this->isDriverAvailable($driver, $trip);

        // if ($tripIntersect != null && $tripIntersect['x'] != -1) {
        //     return response()->json(['error' => 'Driver is not available for the trip'], 422);
        // }




        DB::beginTransaction();
        try {
            //update trip driver
            $trip->driver_id = $driver->id;
            $this->tripRepository->update($trip->id, $trip->toArray());

            $messageAssignment = "You have been assigned to a trip that will start at " . $trip->first_stop_time . " on " . $trip->effective_date . " on route " . $trip->route->name . " and repeated every " . $trip->repetition_period . " days";
            //save notification for the driver
            $newNotification = $this->notificationRepository->create([
                'user_id' => $driver->id,
                'message' => $messageAssignment,
                'seen' => 0,
            ]);
            //send notification to the driver
            $this->sendSingleNotification($driver->fcm_token, $messageAssignment, $newNotification->id);

            DB::commit();
            return response()->json(['success' => ['trip updated successfully'], 'driver' => $driver]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    private function getAllRoutesOfStop($stop_id)
    {
        $routeStopsIds = $this->routeStopRepository->findByWhere(['stop_id' => $stop_id], ['route_id'])->pluck('route_id')->toArray();
        $routes = Route::whereIn("id", $routeStopsIds)->get();
        return $routes;
    }
    private function getRoutePath($route_id, $start_stop_id)
    {
        $startRouteStop = $this->routeStopRepository->findByWhere(['route_id' => $route_id, 'stop_id' => $start_stop_id], ['id', 'order'])->first();
        Log::info("startRouteStop");
        Log::info($startRouteStop->order);
        //Get route_stop ids for the route_stop in the route whose ID is route_id and order is greater than the order of the route_stop whose ID is route_stop_id
        $routeStopIds = $this->routeStopRepository->findByWhere([['route_id', $route_id], ['order', '>', $startRouteStop->order]], ['id'])->pluck('id')->toArray();
        Log::info("routeStopIds");
        Log::info($routeStopIds);

        $directions = RouteStopDirection::whereIn("route_stop_id", $routeStopIds)->whereNotNull('overview_path')->where('current', 1)->get();
        $path = [];
        foreach ($directions as $direction) {
            $path = array_merge($path, json_decode($direction->overview_path));
        }
        return $path;
    }

    private function getDistanceBetweenTwoPoints($point1, $point2)
    {
        $earthRadius = 6371000;
        $lat1 = $point1['lat'];
        $lng1 = $point1['lng'];
        $lat2 = $point2['lat'];
        $lng2 = $point2['lng'];
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $dist = $earthRadius * $c;
        return $dist;
    }

    private function isPointWithinRangeOfPath($point, $path, $range)
    {
        $returnData= new stdClass();
        //check if the point is within the range of the path
        $distances = [];
        foreach ($path as $pathPoint) {
            $distance = $this->distance($point['lat'], $point['lng'], $pathPoint->lat, $pathPoint->lng);
            array_push($distances, $distance);
        }
        if(count($distances) == 0){
            $returnData->isWithinRange = false;
            return $returnData;
        }

        if (min($distances) <= $range) {
            $returnData->isWithinRange = true;
            $returnData->distance = min($distances);
            $returnData->pathPointIndex = array_search(min($distances), $distances);
            $returnData->pathPoint = $path[$returnData->pathPointIndex];
            return $returnData;
        } else {
            $returnData->isWithinRange = false;
            return $returnData;
        }
    }

    public function searchByGuest(Request $request)
    {
        return $this->getTripSearchResults($request, false);
    }

    public function searchByCustomer(Request $request)
    {
        return $this->getTripSearchResults($request, true);
    }

    private function getTripSearchResults(Request $request, bool $authenticated)
    {
        $setting = $this->settingRepository->all(['*'], ['currency'])->first();
        $range = $setting->max_distance_to_stop;
        //validate the request
        $this->validate($request, [
            'start_address' => 'required|string',
            'destination_address' => 'required|string',
            'start_lat' => 'required|numeric',
            'start_lng' => 'required|numeric',
            'end_lat' => 'required|numeric',
            'end_lng' => 'required|numeric',
            'date' => 'required|date',
        ], [], []);

        // $date = $request->date;
        if($request->exists('date')){
            $start_date = $request['date'];
            // remove time from date
            $start_date = new Carbon($start_date);
            //set time to 00:00:00
            $start_date->setTime(0, 0, 0);
        }
        else{
            return response()->json(['error' => ['Invalid date']], 422);
        }
        if($request->exists('start_lat')){
            $startLat = floatval($request['start_lat']);
        }
        if($request->exists('start_lng')){
            $startLng = floatval($request['start_lng']);
        }
        if($request->exists('end_lat')){
            $endLat = floatval($request['end_lat']);
        }
        if($request->exists('end_lng')){
            $endLng = floatval($request['end_lng']);
        }

        if($startLat == null || $startLng == null || $endLat == null || $endLng == null){
            return response()->json(['error' => ['Invalid coordinates']], 422);
        }

        //Get the close start stops to the start point and have route inner join with route_stops table
        $closeStartStops = Stop::select("*", DB::raw("
        ST_Distance_Sphere( point({$startLng}, {$startLat}),
                              point(lng, lat)) * .001
          as `distance`
          "))->having("distance", "<", $range)->get();
        //remove stops that are not in the route stops table
        $closeStartStops = $closeStartStops->filter(function ($stop) {
            return count(RouteStop::where("stop_id", "=", $stop->id)->get()) > 0;
        });

        //get the current currency
        $currency_code = $setting->currency->code;
        $paymentMethod = $this->getPaymentMethod();
        $allow_ads_in_driver_app = $setting->allow_ads_in_driver_app;
        $allow_ads_in_customer_app = $setting->allow_ads_in_customer_app;
        //If there is no stop in the range, return an empty array
        if (count($closeStartStops) == 0) {
            Log::info("No close start stops found");
            return response()->json([
                'trip_search_results' => [],
                'currency_code' => $currency_code,
                'payment_method' => $paymentMethod,
                'allow_ads_in_driver_app' => $allow_ads_in_driver_app,
                'allow_ads_in_customer_app' => $allow_ads_in_customer_app,
            ]);
        }

        $endPoint = [
            'lat' => $endLat,
            'lng' => $endLng,
        ];
        $tripRouteAllData = [];
        $validRoutesIds = [];
        //Loop through the closeStartStops
        foreach ($closeStartStops as $closeStartStop) {
            $distanceToStartStop = $this->distance($startLat, $startLng, $closeStartStop->lat, $closeStartStop->lng);
            //Get all the routes of the stop
            $routes = $this->getAllRoutesOfStop($closeStartStop->id);
            Log::info("routes of stop");
            Log::info(count($routes) . " routes found for " . $closeStartStop->id);
            //Loop through the routes
            for ($route_index=0; $route_index < count($routes); $route_index++) {
                $route = $routes[$route_index];
                Log::info("route");
                Log::info($route);
                $path = $this->getRoutePath($route->id, $closeStartStop->id);
                $retPathData = $this->isPointWithinRangeOfPath($endPoint, $path, $range);
                if ($retPathData->isWithinRange) {
                    //check if duplicate in validRoutesIds
                    if(!in_array($route->id, $validRoutesIds)){
                        //get the closest stop on the route to the end point
                        $returnClosestStopData = $this->getClosestStopOnRoute($route->id, $closeStartStop, $endPoint);
                        $closeEndStop = $returnClosestStopData->stop;
                        $distanceToEndStop = $returnClosestStopData->distance;
                        Log::info("closeEndStop " . $closeEndStop->id . " distance " . $distanceToEndStop);
                        $routePath = array_slice($path, 0, $retPathData->pathPointIndex + 1);
                        $routePriceData = $this->calcPrice($routePath);
                        $routePrice = $routePriceData->price;
                        $routeDistance = $routePriceData->distance;
                        if($routePrice < 1 || $routeDistance < 1){
                            Log::info("route price or distance is less than 1");
                            continue;
                        }
                        //check if a valid trip exists on this route
                        $plannedTrips = $this->plannedTripRepository->findByWhere(
                            [['route_id', '=', $route->id], ['planned_date', '>=', $start_date]],
                            ['*'],
                            ['plannedTripDetail', 'bus']);

                        array_push($validRoutesIds, $route->id);

                        //check if trip is empty
                        if(count($plannedTrips) == 0){
                            Log::info("No planned trips found for route " . $route->id);
                            continue;
                        }
                        foreach ($plannedTrips as $plannedTrip)
                        {
                            //check if ended_at is null
                            if($plannedTrip->ended_at != null){
                                continue;
                            }
                            $priceFactor = $plannedTrip->bus->price_factor;
                            Log::info('price factor' . $priceFactor);
                            $plannedTripDetails = $plannedTrip->plannedTripDetail;
                            $plannedStartTripDetails = $plannedTripDetails->filter(function ($plannedTripDetail) use ($closeStartStop) {
                                return $plannedTripDetail->stop_id == $closeStartStop->id;
                            })->first();
                            if($plannedStartTripDetails == null){
                                Log::info("No planned start trip details found");
                                continue;
                            }
                            $plannedStartTime = new Carbon($plannedStartTripDetails->planned_timestamp);
                            $plannedDate = new Carbon($plannedTrip->planned_date);
                            $plannedDate = $plannedDate->toDateString();
                            $plannedStartDateTime = new Carbon($plannedDate);
                            $plannedStartDateTime->setTime($plannedStartTime->hour, $plannedStartTime->minute,
                             $plannedStartTime->second);

                            $plannedEndTripDetails = $plannedTripDetails->filter(function ($plannedTripDetail) use ($closeEndStop) {
                                return $plannedTripDetail->stop_id == $closeEndStop->id;
                            })->first();
                            if($plannedEndTripDetails == null){
                                Log::info("No planned end trip details found");
                                continue;
                            }
                            $plannedEndTime = new Carbon($plannedEndTripDetails->planned_timestamp);
                            $plannedEndDateTime = new Carbon($plannedDate);
                            $plannedEndDateTime->setTime($plannedEndTime->hour, $plannedEndTime->minute,
                             $plannedEndTime->second);

                            $tripRouteData= new stdClass();
                            $tripRouteData->distanceToStartStop = $distanceToStartStop;
                            $tripRouteData->distanceToEndStop = $distanceToEndStop;
                            $tripRouteData->route = $route;
                            $tripRouteData->startStop = $closeStartStop;
                            $tripRouteData->endStop = $closeEndStop;
                            //get valid path until the end point index
                            $tripRouteData->path = $routePath;
                            $tripRouteData->distanceToEndPoint = $retPathData->distance;
                            $tripRouteData->endPoint = $retPathData->pathPoint;
                            $tripRouteData->trip = $plannedTrip;
                            $tripRouteData->price = $routePrice * $priceFactor;
                            $tripRouteData->distance = $routeDistance;
                            $tripRouteData->driver = $plannedTrip->driver;
                            $tripRouteData->availableSeats = $this->getAvailableSeats($plannedTrip, $closeStartStop, $closeEndStop, $route);
                            $tripRouteData->plannedStartDate = $plannedDate;
                            $tripRouteData->plannedStartDateTime = $plannedStartDateTime;
                            $tripRouteData->plannedStartTime = $plannedStartDateTime->toTimeString();
                            $tripRouteData->plannedEndTime = $plannedEndDateTime->toTimeString();
                            $tripRouteData->startAddress = $request->start_address;
                            $tripRouteData->destinationAddress = $request->destination_address;

                            if($tripRouteData->availableSeats > 0)
                                array_push($tripRouteAllData, $tripRouteData);

                        }
                    }
                    else
                    {
                        Log::info("route " . $route->id . " is already in validRoutesIds");
                    }
                }
            }
        }
        Log::info("tripRouteAllData count " . count($tripRouteAllData));
        if(count($tripRouteAllData) > 0){
            usort($tripRouteAllData, function ($a, $b) {
                return $a->plannedStartDateTime <=> $b->plannedStartDateTime;
            });
            if($authenticated)
            {
                $user = Auth::user();
                Log::info("user");
                //clear TripSearchResults of the user
                $this->tripSearchResultRepository->deleteWhere(['user_id' => $user->id]);
                //save TripSearchResults of the user
                foreach ($tripRouteAllData as $tripRouteData) {

                    $tripSearchResult = [
                        'user_id' => $user->id,
                        'route_id' => $tripRouteData->route->id,
                        'planned_trip_id' => $tripRouteData->trip->id,
                        'start_stop_id' => $tripRouteData->startStop->id,
                        'end_stop_id' => $tripRouteData->endStop->id,
                        'end_point_lat' => $tripRouteData->endPoint->lat,
                        'end_point_lng' => $tripRouteData->endPoint->lng,
                        'distance_to_start_stop' => $tripRouteData->distanceToStartStop,
                        'distance_to_end_stop' => $tripRouteData->distanceToEndStop,
                        'distance_to_end_point' => $tripRouteData->distanceToEndPoint,
                        'start_address' => $tripRouteData->startAddress,
                        'destination_address' => $tripRouteData->destinationAddress,
                        'planned_start_date' => $tripRouteData->plannedStartDate,
                        'planned_start_time' => $tripRouteData->plannedStartTime,
                        'price' => $tripRouteData->price,
                        'distance' => $tripRouteData->distance,
                        'path' => json_encode($tripRouteData->path),
                    ];
                    $savedTripSearchResult = $this->tripSearchResultRepository->create($tripSearchResult);
                    $tripRouteData->id = $savedTripSearchResult->id;
                }
            }
        }
        return response()->json([
            'trip_search_results' => $tripRouteAllData,
            'currency_code' => $currency_code,
            'payment_method' => $paymentMethod,
            'allow_ads_in_driver_app' => $allow_ads_in_driver_app,
            'allow_ads_in_customer_app' => $allow_ads_in_customer_app,
        ]);
    }
    private function getAvailableSeats($plannedTrip, $startStop, $endStop, $route)
    {
        Log::info("getAvailableSeats for trip ". $plannedTrip->id);
        //count all reservations for the trip from startStop to endStop
        $tripReservations = $this->reservationRepository->findByWhere(
            ['planned_trip_id' => $plannedTrip->id], ['*'], ['plannedTrip']);
        $reservationCount = 0;

        $route_id = $route->id;
        $start_stop_id = $startStop->id;
        $end_stop_id = $endStop->id;
        $startRouteStop = $this->routeStopRepository->findByWhere(['route_id' => $route_id, 'stop_id' => $start_stop_id], ['id', 'order'])->first();
        $endRouteStop = $this->routeStopRepository->findByWhere(['route_id' => $route_id, 'stop_id' => $end_stop_id], ['id', 'order'])->first();

        //get all stops from startStop to endStop on the route
        $routeStops = $this->routeStopRepository->findByWhere([
            ['route_id', '=', $route->id],
            ['order', '>=', $startRouteStop->order],
            ['order', '<=', $endRouteStop->order]
        ], ['*'], ['stop']);
        $routeStopsIds = $routeStops->pluck('id')->toArray();
        $reservedStops = $this->routeStopRepository->findByWhereIn('id', $routeStopsIds, ['stop_id'])->pluck('stop_id')->toArray();
        // Log::info("routeStopsIds");
        // Log::info($routeStopsIds);
        // Log::info("reservedStops");
        // Log::info($reservedStops);
        foreach ($tripReservations as $tripReservation) {
            $reservationStopIDs = $this->getReservationStopIDs($tripReservation);
            // Log::info("reservationStopIDs");
            // Log::info($reservationStopIDs);

            // check if the routeStops intersect with the reservationStopIDs
            $intersection = array_intersect($reservationStopIDs, $reservedStops);
            if (count($intersection) > 0) {
                $reservationCount ++;
            }
            // //check if $startStop is in the reservationStopIDs
            // if (in_array($startStop->id, $reservationStopIDs) ||
            //     in_array($endStop->id, $reservationStopIDs)) {
            //     $reservationCount ++;
            // }
        }
        Log::info("The trip has ". $reservationCount. " reservations");
        // get the capacity of the bus
        $capacity = $plannedTrip->bus->capacity;

        $availableSeats = $capacity - $reservationCount;


        return $availableSeats;
    }

    private function getReservationStopIDs($tripReservation)
    {

        $reservationStopIDs = [];
        $startStopID = $tripReservation->start_stop_id;
        $endStopID = $tripReservation->end_stop_id;
        array_push($reservationStopIDs, $startStopID);
        $routeID = $tripReservation->plannedTrip->route_id;
        // Log::info("startStopID");
        // Log::info($startStopID);
        // Log::info("routeID");
        // Log::info($routeID);
        $startRouteStop = $this->routeStopRepository->findByWhere(['route_id' => $routeID, 'stop_id' => $startStopID], ['id', 'order'])->first();
        $endRouteStop = $this->routeStopRepository->findByWhere(['route_id' => $routeID, 'stop_id' => $endStopID], ['id', 'order'])->first();
        //Get route_stop ids for the route_stop in the route whose ID is route_id and order is greater than the order of the route_stop whose ID is route_stop_id
        $routeStopIds = $this->routeStopRepository->findByWhere([
            ['route_id', $routeID],
            ['order', '>=', $startRouteStop->order],
            ['order', '<=', $endRouteStop->order],
            //['stop_id', '!=', $endStopID]
        ], ['id'])->pluck('id')->toArray();
        // Log::info("routeStopIds");
        // Log::info($routeStopIds);
        //get stops for the route_stop_ids
        $stops = $this->routeStopRepository->findByWhereIn('id', $routeStopIds, ['stop_id'])->pluck('stop_id')->toArray();
        //add to reservationStopIDs
        $reservationStopIDs = array_merge($reservationStopIDs, $stops);
        return $reservationStopIDs;
    }

    private function getClosestStopOnRoute($routeId, $closeStartStop, $point)
    {
        $routeStops = $this->routeStopRepository->findByWhere([['route_id', '=', $routeId], ['stop_id', '!=', $closeStartStop->id]], ['*'], ['stop']);
        $closestStop = null;
        $closestDistance = INF;
        foreach ($routeStops as $routeStop) {
            $distance = $this->distance($point['lat'], $point['lng'], $routeStop->stop->lat, $routeStop->stop->lng);
            if ($distance < $closestDistance) {
                $closestStop = $routeStop->stop;
                $closestDistance = $distance;
            }
        }
        $returnData= new stdClass();
        $returnData->stop = $closestStop;
        $returnData->distance = $closestDistance;
        return $returnData;
    }

    //Calculate price for a trip
    public function calcPrice($path)
    {
        if($path == null){
            return 0;
        }
        $retPriceData= new stdClass();
        $distance = 0;
        foreach ($path as $index => $point) {
            if ($index > 0) {
                $distance += $this->distance($path[$index - 1]->lat, $path[$index - 1]->lng, $point->lat, $point->lng);
            }
        }
        $currentSettings = Setting::where("id", 1)->first();
        $ratePerKm = $currentSettings->rate_per_km;
        $commission = $currentSettings->commission;
        $orgPrice = $distance * $ratePerKm;
        $commissionFactor = (1 + $commission/100.0);
        $price = $orgPrice * $commissionFactor;
        $retPriceData->distance = $distance;
        $retPriceData->orgPrice = $orgPrice;
        $retPriceData->commissionFactor = $commissionFactor;
        $retPriceData->price = $price;
        return $retPriceData;
    }



    public function pay(Request $request)
    {
        //validate the request
        $validator = $this->validate($request, [
            'trip_search_result_id' => 'required|integer',
            'payment_method' => 'required|integer',
            'coupon_code' => 'string|nullable',
        ], [] , []);

        $coupon_code = $request->coupon_code;

        $trip_search_result_id = $request->trip_search_result_id;

        $trip_search_result = $this->tripSearchResultRepository->findById($trip_search_result_id);

        $planned_trip_id = $trip_search_result->planned_trip_id;

        $trip = $this->plannedTripRepository->findById($planned_trip_id);
        if ($trip == null) {
            return response()->json(["message" => "Trip not found", "success" => false], 404);
        }

        $user = Auth::user();
        $user_id = $user->id;

        $trip_search_result_user_id = $trip_search_result->user_id;
        if ($trip_search_result_user_id != $user_id) {
            return response()->json(["message" => "Unauthorized", "success" => false], 401);
        }

        $currentSettings = Setting::where("id", 1)->first();
        $commission = $currentSettings->commission;

        $payment_method = $request->payment_method;

        $systemPaymentMethod = $this->getPaymentMethod();
        if($systemPaymentMethod != "none"){
            $price = $trip_search_result->price;
        }
        else{
            $price = 0.0;
        }
        $priceBeforeDiscount = $price;
        $priceAfterDiscount = $price;

        DB::beginTransaction();
        try {
            $discount = 0.0;
            if($coupon_code != null && $price != 0.0)
            {
                $coupon = $this->couponRepository->findByWhere(['code' => $coupon_code])->first();
                if($coupon != null && $coupon->status == 0 && $coupon->expiration_date >= date('Y-m-d H:i:s'))
                {
                    //check if the coupon is used
                    $couponsCustomer = $this->couponCustomerRepository->findByWhere(['coupon_id' => $coupon->id, 'user_id' => $user_id]);
                    $coupon_valid = false;
                    if(!$couponsCustomer->isEmpty())
                    {
                        //check if the coupon is used for the planned trip
                        $usedForPlannedTrip = $couponsCustomer->where('planned_trip_id', $planned_trip_id);
                        if($usedForPlannedTrip->isEmpty())
                        {
                            $usedCount = $couponsCustomer->count();
                            if($coupon->limit == 0 || $usedCount < $coupon->limit)
                            {
                                $coupon_valid = true;
                            }
                        }
                    }
                    else
                    {
                        $coupon_valid = true;
                    }
                    if($coupon_valid)
                    {
                        Log::info("coupon " . $coupon->id . " will be applied for ". $price . " with discount " . $coupon->discount);
                        $maxAmount = $coupon->max_amount;
                        $discountAmount = $coupon->discount * $price / 100;
                        if($maxAmount > 0 && $discountAmount > $maxAmount)
                        {
                            $discountAmount = $maxAmount;
                        }
                        if($discountAmount > $price)
                        {
                            $discountAmount = $price;
                        }

                        $discount = $discountAmount;
                        $priceAfterDiscount = $price - $discount;
                        $couponCustomer = $this->couponCustomerRepository->create([
                            'coupon_id' => $coupon->id,
                            'user_id' => $user_id,
                            'planned_trip_id' => $planned_trip_id,
                        ]);
                    }
                }
            }

            if($payment_method == 0) //wallet
            {
                $user_balance = $user->wallet;
                if ($user_balance < $priceAfterDiscount) {
                    return response()->json(["message" => "Insufficient funds", "success" => false], 400);
                }

                $user->wallet = $user_balance - $priceAfterDiscount;
                $user->save();

                // //driver share
                // $driver_share = $price * (1 - $commission/100.0);
                // //admin share
                // $admin_share = $price * $commission/100.0;
            }

            //driver share
            $driver_share = $priceBeforeDiscount * (1 - $commission/100.0);
            //admin share
            $admin_share = $priceAfterDiscount - $driver_share;
            if($admin_share < 0)
            {
                $admin_share = 0;
            }

            //Reservation
            $reservation = $this->reservationRepository->create([
                "user_id" => $user_id,
                "ticket_number" => uniqid(),
                "planned_trip_id" => $planned_trip_id,
                "paid_price" => $priceAfterDiscount,
                "trip_price" => $priceBeforeDiscount,
                "admin_share" => $admin_share,
                "driver_share" => $driver_share,
                "payment_method" => $payment_method,
                "reservation_date" => Carbon::now(),
                "start_stop_id" => $trip_search_result->start_stop_id,
                "end_stop_id" => $trip_search_result->end_stop_id,
                "end_point_lat" => $trip_search_result->end_point_lat,
                "end_point_lng" => $trip_search_result->end_point_lng,
                "start_address" => $trip_search_result->start_address,
                "destination_address" => $trip_search_result->destination_address,
                "planned_start_time" => $trip_search_result->planned_start_time,
                "status_id" => 1,
            ]);

            DB::commit();
            return response()->json(["message" => "Payment successful", "success" => true, "new_wallet_balance" => $user->wallet], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    //getPlannedTripDetails
    public function getPlannedTripDetails($planned_trip_id)
    {
        Log::info("getPlannedTripDetails");

        $planned_trip = $this->plannedTripRepository->findById($planned_trip_id, ['*'], ['plannedTripDetail.stop']);
        if ($planned_trip == null) {
            return response()->json(["message" => "Trip not found", "success" => false], 404);
        }

        Log::info("planned_trip");
        Log::info($planned_trip);

        $user = Auth::user();
        $user_id = $user->id;

        $planned_trip_details = $planned_trip->plannedTripDetail;

        // $planned_trip_details = $planned_trip_details->map(function ($planned_trip_detail) {
        //     $planned_trip_detail->stop = $planned_trip_detail->stop;
        //     return $planned_trip_detail;
        // });

        // $planned_trip->plannedTripDetail = $planned_trip_details;

        return response()->json(["success" => true, "trip" => $planned_trip], 200);
    }


    //startStopPlannedTrip
    public function startStopPlannedTrip(Request $request)
    {
        Log::info("startStopPlannedTrip");

        //validate the request
        $this->validate($request, [
            'planned_trip_id' => 'required|integer',
            'mode' => 'required|integer',
        ], [], []);

        $planned_trip_id = $request->planned_trip_id;

        $planned_trip = $this->plannedTripRepository->findById($planned_trip_id);

        //get the planned trip with all reservations
        $planned_trip = $this->plannedTripRepository->findById($planned_trip_id, ['*'], ['reservations.customer', 'route']);

        if ($planned_trip == null) {
            return response()->json(["message" => "Trip not found", "success" => false], 404);
        }

        $user = Auth::user();
        $user_id = $user->id;

        //check if the user is the driver of the trip
        if ($planned_trip->driver_id != $user_id) {
            return response()->json(["message" => "Unauthorized", "success" => false], 401);
        }

        DB::beginTransaction();
        try {
            $mode = $request->mode; // 1: start, 0: end
            $eventTime = Carbon::now();
            if ($mode == 1) {
                $planned_trip->started_at = $eventTime;
            } else {
                $planned_trip->ended_at = $eventTime;
            }
            $planned_trip->save();
            if ($mode == 1)
            {
                // get all reservations for the planned_trip_id with ride_status = 0 or 1
                $reservations = $planned_trip->reservations->filter(function ($reservation) {
                    return $reservation->ride_status == 0 || $reservation->ride_status == 1;
                });
                if (!($reservations == null || count($reservations) == 0)) {
                    //get all customers of the reservations
                    $customers = $reservations->pluck('customer');
                    $customerIds = $customers->pluck('id')->toArray();
                    $tokens = $customers->pluck('fcm_token')->toArray();
                    //save the notification for all customers
                    // $notificationData = [];
                    for ($i=0; $i < count($customerIds); $i++) {
                        $customerId = $customerIds[$i];
                        //format $eventTime
                        //$eventTime = $eventTime->toDayDateTimeString();
                        $startTripMessage = "Your trip on route " . $planned_trip->route->name . " has started at " . $eventTime . ". Please be at the stop on time";
                        $newNotification = $this->notificationRepository->create([
                            'user_id' => $customerId,
                            'message' => $startTripMessage,
                            'seen' => 0,
                        ]);
                        $notificationId = $newNotification->id;
                        $token = $tokens[$i];
                        $this->sendSingleNotification($token, $request->message, $notificationId);
                    }
                }
            }
            DB::commit();
            return response()->json(['success'=> true], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }


        return response()->json(["success" => true, "trip" => $planned_trip], 200);
    }


    //setLastPosition
    public function setLastPosition(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'planned_trip_id' => 'required|integer',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'required|numeric',
        ], [], []);

        $planned_trip_id = $request->planned_trip_id;

        $planned_trip = $this->plannedTripRepository->findById($planned_trip_id, ['*'], ['plannedTripDetail.stop']);
        if ($planned_trip == null) {
            return response()->json(["message" => "Trip not found", "success" => false], 404);
        }

        $user = Auth::user();
        $user_id = $user->id;

        //check if the user is the driver of the trip
        if ($planned_trip->driver_id != $user_id) {
            return response()->json(["message" => "Unauthorized", "success" => false], 401);
        }

        $setting = $this->settingRepository->all()->first();
        $distance_to_stop_to_mark_arrived = $setting->distance_to_stop_to_mark_arrived;

        //create a transaction
        DB::beginTransaction();
        try {
            $lat = $request->lat;
            $lng = $request->lng;
            $speed = $request->speed;

            // Log::info("lat = " . $lat . ", lng = " . $lng . ", speed = " . $speed);

            $planned_trip->last_position_lat = $lat;
            $planned_trip->last_position_lng = $lng;

            $planned_trip->save();

            $pos = array(
                'speed' => $speed,
                'lat' => $planned_trip->last_position_lat,
                'lng' => $planned_trip->last_position_lng);
            broadcast(new \App\Events\TripPositionUpdated($planned_trip->channel, json_encode($pos)));
            // Log::info("TripPositionUpdated on channel " . $planned_trip->channel);

            //loop through the plannedTripDetails stops
            $planned_trip_details = $planned_trip->plannedTripDetail;

            //get the next stop which do not have actual_timestamp
            $planned_trip_detail = $planned_trip_details->filter(function ($planned_trip_detail) {
                return $planned_trip_detail->actual_timestamp == null;
            })->first();

            // Log::info("planned_trip_detail" . $planned_trip_detail);

            if ($planned_trip_detail != null) {
                $next_stop = $planned_trip_detail->stop;
                // Log::info("next_stop " . $next_stop);
                $next_stop_planned_time = $planned_trip_detail->planned_timestamp;

                $allPassengers = $this->reservationRepository->findByWhere([['planned_trip_id', '=', $planned_trip_id]]);

                //get all complaints for all reservations (passengers)
                $allComplaints = $this->complaintRepository->findByWhereIn('reservation_id', $allPassengers->pluck('id')->toArray());



                // filter $allPassengers based on ride_status
                $passengersToBePickedUp = $allPassengers->filter(function ($passenger) use($next_stop) {
                    return $passenger->ride_status == 0 && $passenger->start_stop_id == $next_stop->id;
                });

                $passengersToBeDroppedOff = $allPassengers->filter(function ($passenger) use($lat, $lng) {
                    //check ride_status == 1 and drop off point is near the current position
                    return ($passenger->ride_status == 1) &&
                    ($this->distance($lat, $lng, $passenger->end_point_lat, $passenger->end_point_lng) < 2);
                });

                $passengersToBeDroppedOff = $passengersToBeDroppedOff->values();

                $distance = $this->distance($lat, $lng, $next_stop->lat, $next_stop->lng)*1000;
                // Log::info("distance = " . $distance . ", stop_id = " . $next_stop->id);
                if ($distance < $distance_to_stop_to_mark_arrived && count($passengersToBePickedUp) == 0) {
                    $planned_trip_detail->actual_timestamp = Carbon::now();
                    $planned_trip_detail->save();
                    //check if there are complaints in this stop
                    $complaintsForStop = $allComplaints->filter(function ($complaint) use($next_stop) {
                        return $complaint->stop_id == $next_stop->id;
                    });

                    // Log::info("complaintsForStop" . $complaintsForStop);

                    //set the actual timestamp for the complaints
                    foreach ($complaintsForStop as $complaint) {
                        $complaint->actual_time = Carbon::now();
                        $complaint->save();
                    }
                }

                $distanceToNextStop = $this->distance($lat, $lng, $next_stop->lat, $next_stop->lng) * 1000;

                // Log::info("next_stop " . $next_stop->address . ", distanceToNextStop = " . $distanceToNextStop . ", passengersToBePickedUp = " . $passengersToBePickedUp . ", passengersToBeDroppedOff = " . $passengersToBeDroppedOff);

                DB::commit();
                return response()->json([
                    "success" => true,
                    "next_stop" => $next_stop,
                    "next_stop_planned_time" => $next_stop_planned_time,
                    "distance_to_next_stop" => $distanceToNextStop,
                    "count_passengers_to_be_picked_up" => count($passengersToBePickedUp),
                    "passengers_to_be_dropped_off" => $passengersToBeDroppedOff,
                ], 200);
            }
            else
            {
                DB::commit();
                return response()->json([
                    "success" => true,
                    "next_stop" => null,
                    "distance_to_next_stop" => 0,
                    "count_passengers_to_be_picked_up" => 0,
                    "passengers_to_be_dropped_off" => null,
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    //pick-up a passenger
    public function pickUp(Request $request)
    {
        //validate
        $this->validate($request, [
            'ticket_number' => 'required|nullable|string',
            'planned_trip_id' => 'required|integer',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'required|numeric',
        ], [], []);

        $user = Auth::user();
        $driver_id = $user->id;

        $setting = $this->settingRepository->all()->first();
        $distance_to_stop_to_mark_arrived = $setting->distance_to_stop_to_mark_arrived;

        $ticket_number = $request->ticket_number;
        $planned_trip_id = $request->planned_trip_id;
        $lat = $request->lat;
        $lng = $request->lng;
        $speed = $request->speed;

        Log::info("pickUp" . ", ticket_number = " . $ticket_number . ", planned_trip_id = " . $planned_trip_id . ", driver_id = " . $driver_id . ", lat = " . $lat . ", lng = " . $lng . ", speed = " . $speed);

        if($ticket_number !== "null")
        {
            $reservation = $this->reservationRepository->findByWhere([['ticket_number', '=', $ticket_number]],
            ['*'], ['firstStop', 'plannedTrip'])->first();

            if ($reservation == null) {
                return response()->json(["message" => "Reservation not found", "success" => false], 404);
            }

            if ($reservation->planned_trip_id != $planned_trip_id) {
                return response()->json(["message" => "Reservation not found", "success" => false], 404);
            }

            $planned_trip = $reservation->plannedTrip;

            if($planned_trip == null){
                return response()->json(["message" => "Planned trip not found", "success" => false], 404);
            }

            if ($planned_trip->driver_id != $driver_id) {
                return response()->json(["message" => "Unauthorized", "success" => false], 500);
            }

            if($reservation->ride_status != 0){
                return response()->json(["message" => "Passenger already picked up", "success" => false], 500);
            }

            //check if the passenger is near the stop
            $distance = $this->distance($lat, $lng, $reservation->firstStop->lat, $reservation->firstStop->lng)*1000;

            if($distance > $distance_to_stop_to_mark_arrived){
                return response()->json(["message" => "Passenger is not near the stop", "success" => false], 500);
            }

            $reservation->ride_status = 1;
            $reservation->save();

            $this->updatePayment($reservation);

            return $this->setLastPosition($request);
        }
        else
        {
            //get all reservations for the planned_trip_id with ride_status = 0
            $reservations = $this->reservationRepository->findByWhere([['planned_trip_id', '=', $planned_trip_id], ['ride_status', '=', 0]],
            ['*'], ['firstStop', 'plannedTrip']);



            if ($reservations == null || count($reservations) == 0) {
                return response()->json(["message" => "Reservation not found", "success" => false], 404);
            }

            $reservation_found = false;

            // loop through the reservations and check if the passenger is near the stop
            foreach ($reservations as $reservation) {
                $planned_trip = $reservation->plannedTrip;
                if ($planned_trip->driver_id != $driver_id) {
                    return response()->json(["message" => "Unauthorized", "success" => false], 500);
                }

                $distance = $this->distance($lat, $lng, $reservation->firstStop->lat, $reservation->firstStop->lng)*1000;
                if($distance < $distance_to_stop_to_mark_arrived){
                    $reservation->ride_status = 2; //missed
                    $reservation->save();
                    $reservation_found = true;
                }
            }
            if($reservation_found == false){
                return response()->json(["message" => "No passenger found", "success" => false], 500);
            }

            $this->updatePayment($reservation);

            return $this->setLastPosition($request);
        }
    }

    private function updatePayment($reservation)
    {
        DB::beginTransaction();
        try {
            $planned_trip = $reservation->plannedTrip;
            $driver_id = $planned_trip->driver_id;
            $paymentDate = Carbon::now();

            $admin = User::where('role', 0)->first();
            $admin_id = $admin->id;
            $this->userPaymentRepository->create([
                "user_id" => $admin_id,
                "amount" => $reservation->admin_share,
                "payment_date" => $paymentDate,
                "reservation_id" => $reservation->id,
            ]);

            // update admin wallet
            $admin->wallet += $reservation->admin_share;
            $admin->save();

            if($reservation->payment_method==0) //wallet
            {
                $this->userPaymentRepository->create([
                    "user_id" => $driver_id,
                    "amount" => $reservation->driver_share,
                    "payment_date" => $paymentDate,
                    "reservation_id" => $reservation->id,
                ]);

                // update drive wallet
                $driver = $this->userRepository->findById($driver_id);
                $driver->wallet += $reservation->driver_share;
                $driver->save();
            }
            else if($reservation->payment_method==1) //cash
            {
                $amount = 0;
                if($reservation->paid_price < $reservation->driver_share)
                {
                    $amount = $reservation->driver_share - $reservation->paid_price;
                }
                $this->userPaymentRepository->create([
                    "user_id" => $driver_id,
                    "amount" => $amount,
                    "payment_date" => $paymentDate,
                    "reservation_id" => $reservation->id,
                ]);

                $driver = $this->userRepository->findById($driver_id);
                $driver->wallet += $amount;
                $driver->wallet -= $reservation->admin_share;
                $driver->save();
            }

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
        }
    }

    //drop-off a passenger
    public function dropOff(Request $request)
    {
        //validate
        $this->validate($request, [
            'planned_trip_id' => 'required|integer',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'required|numeric',
        ], [], []);

        $user = Auth::user();
        $driver_id = $user->id;

        $setting = $this->settingRepository->all()->first();
        $distance_to_stop_to_mark_arrived = $setting->distance_to_stop_to_mark_arrived;

        $planned_trip_id = $request->planned_trip_id;
        $lat = $request->lat;
        $lng = $request->lng;
        $speed = $request->speed;

        //get all reservations for the planned_trip_id with ride_status = 1
        $reservations = $this->reservationRepository->findByWhere([['planned_trip_id', '=', $planned_trip_id], ['ride_status', '=', 1]],
        ['*'], ['plannedTrip']);

        if ($reservations == null || count($reservations) == 0) {
            return response()->json(["message" => "Reservation not found", "success" => false], 404);
        }

        $reservation_found = false;

        // loop through the reservations and check if the passenger is near the stop
        foreach ($reservations as $reservation) {
            $planned_trip = $reservation->plannedTrip;
            if ($planned_trip->driver_id != $driver_id) {
                return response()->json(["message" => "Unauthorized", "success" => false], 500);
            }
            $distance = $this->distance($lat, $lng, $reservation->end_point_lat, $reservation->end_point_lng)*1000;
            if($distance < $distance_to_stop_to_mark_arrived){
                $reservation->ride_status = 3; //drop off
                $reservation->save();
                $reservation_found = true;
            }
        }
        if($reservation_found == false){
            return response()->json(["message" => "No drop-off passenger found", "success" => false], 500);
        }

        return $this->setLastPosition($request);

    }

    //notify
    public function notify(Request $request)
    {
        //validate
        $request->validate([
            'id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $id = $request->id;

        //get the planned trip with all reservations
        $planned_trip = $this->plannedTripRepository->findById($id, ['*'], ['reservations.customer', 'driver']);

        // get all reservations for the planned_trip_id with ride_status = 0 or 1
        $reservations = $planned_trip->reservations->filter(function ($reservation) {
            return $reservation->ride_status == 0 || $reservation->ride_status == 1;
        });

        if (!($reservations == null || count($reservations) == 0)) {
            DB::beginTransaction();
            try {
                //get all customers of the reservations
                $customers = $reservations->pluck('customer');
                $customerIds = $customers->pluck('id')->toArray();
                $tokens = $customers->pluck('fcm_token')->toArray();
                //save the notification for all customers
                // $notificationData = [];
               for ($i=0; $i < count($customerIds); $i++) {
                //foreach ($customerIds as $customerId) {
                    $customerId = $customerIds[$i];
                    $newNotification = $this->notificationRepository->create([
                        'user_id' => $customerId,
                        'message' => $request->message,
                        'seen' => 0,
                    ]);
                    $notificationId = $newNotification->id;
                    $token = $tokens[$i];
                    $this->sendSingleNotification($token, $request->message, $notificationId);
                }

                //get the driver of the planned trip
                $driver = $planned_trip->driver;
                //save notification for the driver
                $newNotification = $this->notificationRepository->create([
                    'user_id' => $driver->id,
                    'message' => $request->message,
                    'seen' => 0,
                ]);
                //send notification to the driver
                $this->sendSingleNotification($driver->fcm_token, $request->message, $newNotification->id);
                DB::commit();
                return response()->json(['success'=> true], 200);
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }
        else
        {
            return response()->json(['message' => 'No passenger found', 'success'=> false], 404);
        }
    }

    public function sendNotification($deviceTokens, $message_content)
    {
        $message = CloudMessage::fromArray([
            'notification' => [
                'title' => 'Alert',
                'body' => $message_content,
            ],
            'data' => [
                "title" => "Alert",
                "body" => $message_content,
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'alert' => [
                            'body' => $message_content,
                        ],
                    ],
                ],
            ],
        ]);

        $report = $this->messaging->sendMulticast($message, $deviceTokens);

        if ($report->hasFailures()) {
            $failures = $report->getItems();
            foreach ($failures as $failure) {
                $error = $failure->error();
                Log::info($error->getMessage());
            }
        }
    }

    public function sendSingleNotification($deviceToken, $message_content, $notificationId)
    {
        try
        {
            $message = CloudMessage::fromArray([
                'token' => $deviceToken,
                'notification' => [
                    'title' => 'Alert',
                    'body' => $message_content,
                ],
                'data' => [
                    "title" => "Alert",
                    "body" => $message_content,
                    "id" => $notificationId,
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'alert' => [
                                'body' => $message_content,
                            ],
                        ],
                    ],
                ],
            ]);

            $report = $this->messaging->send($message);
            Log::info("sendSingleNotification "  . $deviceToken . ", " . $message_content . ", " . $notificationId);
        }
        catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
