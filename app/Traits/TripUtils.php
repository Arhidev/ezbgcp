<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\PlannedTrip;
use App\Models\PlannedTripDetail;
use App\Models\Trip;
use DB;
use App\Models\Setting;
trait TripUtils {

    public function publishTrips()
    {
        $currentSettings = Setting::where("id", 1)->first();
        $publish_trips_future_days = $currentSettings->publish_trips_future_days;
        $currentTime = Carbon::now();
        $trips = Trip::with('driver.bus')->where('status_id', 1)->where('driver_id', '!=', null)->get();
        $today = Carbon::today();
        $end = new Carbon($today);
        $end->setTime(23, 59, 59);
        $end->add($publish_trips_future_days, 'days');
        // Log::info('Now: ' . $currentTime. ' Publishing trips from ' . $today->__toString() . ' to ' . $end->__toString());
        $count = 0;
        foreach ($trips as $trip) {
            $startDate = clone $today;
            $endDate = clone $end;
            list($startCal, $events) = $this->getAllEvents($trip, $startDate, $endDate);
            if(count($events) > 0)
            {
                foreach ($events as $event) {
                    if($event['status'] == 1)
                    {
                        //create transaction
                        DB::beginTransaction();
                        try {
                            $plannedTrip = PlannedTrip::create([
                                'channel' => $trip->channel,
                                'trip_id' => $trip->id,
                                'route_id' => $trip->route_id,
                                'planned_date' => $event['start'],
                                'driver_id' => $trip->driver_id,
                                'bus_id' => $trip->driver->bus->id,
                            ]);
                            //get all stops for this trip
                            $tripDetails = $trip->tripDetails()->get();
                            //save PlannedTripDetails
                            foreach ($tripDetails as $tripDetail) {
                                PlannedTripDetail::create([
                                    'planned_trip_id' => $plannedTrip->id,
                                    'stop_id' => $tripDetail->stop_id,
                                    'planned_timestamp' => $tripDetail->planned_timestamp,
                                ]);
                            }
                            $count++;
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollback();
                        }
                    }
                }
            }
        }
        Log::info('Published ' . $count . ' trips');


        //delete published trips that was old and has no reservations
        // get current date - 1 days
        $dateBefore = Carbon::today();
        $dateBefore->subDays(1);

        // get all PlannedTrip that has no reservations and planned_date is less than $dateBefore
        $oldTrips = PlannedTrip::with('reservations')->where('planned_date', '<', $dateBefore)->get();
        $toBeDeletedTripsIds = [];
        foreach ($oldTrips as $oldTrip) {
            //count the reservations
            if(count($oldTrip->reservations) == 0)
            {
                array_push($toBeDeletedTripsIds, $oldTrip->id);
            }
        }
        //delete toBeDeletedTripsIds in DB using Where in
        Log::info('Deleting trips before ' . $dateBefore->__toString() .' that has no reservations with total count of ' . count($toBeDeletedTripsIds));
        // delete trip_search_results that has planned_trip_id in $toBeDeletedTripsIds
        DB::table('trip_search_results')->whereIn('planned_trip_id', $toBeDeletedTripsIds)->delete();
        PlannedTrip::whereIn('id', $toBeDeletedTripsIds)->delete();

        //get all planned trips without a driver or without a bus
        $plannedTripsIds = PlannedTrip::where('driver_id', null)->orWhere('bus_id', null)->pluck('id');
        //delete all
        Log::info('count($plannedTripsIds) '. count($plannedTripsIds) .'');
        PlannedTrip::whereIn('id', $plannedTripsIds)->delete();
    }

    private function getAllEvents($trip, $start, $end, $suspension_id = null)
    {
        //Log::info("Start ". $start);
        $effective_date = new Carbon($trip->effective_date);
        $events = [];
        $startCal = null;
        if ($trip->repetition_period == 0) {
            $startCal = $effective_date->__toString();
            $event = $this->generateEvent($trip, $effective_date, $suspension_id);
            array_push($events, $event);
        } else if ($end > $effective_date) {
            //Log::info('Generating events for trip ' . $trip->id);
            $timestamp = $start;
            if ($effective_date > $start)
                $timestamp = $effective_date;

            //Log::info('Start date is ' . $timestamp->__toString());
            $timestamp = $timestamp->setTimeFromTimeString($trip->first_stop_time);
            while ($timestamp <= $end) {
                $event = $this->generateEvent($trip, $timestamp, $suspension_id);
                array_push($events, $event);
                $timestamp->add($trip->repetition_period, 'days');
                //Log::info('Adding ' . $trip->repetition_period . ' days to ' . $timestamp->__toString());
            }
        }


        return array($startCal, $events);
    }

    private function generateEvent($trip, $timestamp, $suspension_id = null)
    {
        $eventColor = "secondary";

        $eventDriver = $trip->driver;
        $status = 1;
        if ($eventDriver) {
            $eventColor = "success";
        }

        $suspension_id = $this->checkSuspendedTrip($trip, $timestamp, $suspension_id);
        if ($suspension_id) {
            $eventColor = "error";
            $status = 0;
        }

        $event = [
            'start' => $timestamp->__toString(),
            'color' => $eventColor,
            'timed' => true,
            'driver' => $eventDriver,
            'status' => $status,
            'suspension_id' => $suspension_id,
        ];

        return $event;
    }

    public function checkSuspendedTrip($trip, $date, $suspension_id = null)
    {

        $suspensions = $trip->suspensions;
        $found_suspension_id = null;
        if ($suspensions) {
            foreach ($suspensions as $key => $suspension) {
                $suspend_date = new Carbon($suspension->date);
                $repetition = $suspension->repetition_period;

                if ($date >= $suspend_date) {
                    $diff = $date->diffInDays($suspend_date);
                    if (($repetition == 0 && $diff == 0) || (($repetition != 0) && (($diff % $repetition) == 0))) {
                        $found_suspension_id = $suspension->id;
                    }
                }
                if ($suspension_id != null) {
                    if ($suspension_id == $found_suspension_id) {
                        break;
                    } else {
                        $found_suspension_id = null;
                    }
                }
            }
        }
        return $found_suspension_id;
    }

    //Calculate the distance between two points in km's using lat and lng
    public function distance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344);
    }
}
