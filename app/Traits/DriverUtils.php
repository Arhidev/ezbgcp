<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
trait DriverUtils {
    public function isDriverAvailable($driver, $newTrip)
    {
        $tripIntersect = null;
        //get trips for this driver
        $driverTrips = $driver->trips;

        foreach ($driverTrips as $trip) {
            if($trip->id == $newTrip->id)
            {
                return null;
            }
            $tripIntersect = $this->isTripsIntersect($newTrip, $trip);
            if($tripIntersect)
            {
                break;
            }
        }
        return $tripIntersect;
    }

    public function isTripsIntersect($newTrip, $trip)
    {
        $trip_repetition = $trip->repetition_period;
        $newTrip_repetition = $newTrip->repetition_period;

        $trip_effective_date = new Carbon($trip->effective_date);
        $newTrip_effective_date = new Carbon($newTrip->effective_date);
        if($newTrip_effective_date < $trip_effective_date)
        {
            $newTripStart = $newTrip_effective_date->day;
            $tripStart = $newTripStart + $newTrip_effective_date->diffInDays($trip_effective_date);
        }
        else
        {
            $tripStart = $trip_effective_date->day;
            $newTripStart = $tripStart + $newTrip_effective_date->diffInDays($trip_effective_date);
        }

        $orgTripStart = $tripStart;
        $orgNewTripStart = $newTripStart;
        if($trip_repetition != 0)
        {
            $tripStart = $tripStart % $trip_repetition;
        }
        if($newTrip_repetition != 0)
        {
            $newTripStart = $newTripStart % $newTrip_repetition;
        }

        $repetitions = array($newTrip_repetition, $trip_repetition);
        $rem = array($newTripStart, $tripStart);
        $x = $this->findMinX($repetitions, $rem);

        $tripIntersectData = [];
        if($x != -1)
        {
            //show when the trips intersect
            $name1 = "";
            if($trip->driver)
            {
                $name1 = $trip->driver->name;
            }
            $name2 = "";
            if($newTrip->driver)
            {
                $name2 = $newTrip->driver->name;
            }
            if($newTrip_effective_date == $trip_effective_date)
            {
                $tripIntersectDate = new Carbon($trip->effective_date);
            }
            else if($newTrip_effective_date > $trip_effective_date){
                $tripIntersectDate = new Carbon($newTrip->effective_date);
                $tripIntersectDate = $tripIntersectDate->addDays($tripStart * $newTrip->repetition_period);
            }
            else
            {
                $tripIntersectDate = new Carbon($trip->effective_date);
                $tripIntersectDate = $tripIntersectDate->addDays($newTripStart * $trip->repetition_period);
            }

            // $tripIntersectDate = new Carbon($trip->effective_date);
            // $tripIntersectDate = $tripIntersectDate->addDays($newTripStart * $trip->repetition_period);
            // Log::info('Trips intersect at ' . $tripIntersectDate . ' for driver: ' . $name1 . ' and ' . $name2);

            $tripIntersectData = [
                'intersect_date' => $tripIntersectDate,
                'trip' => $trip,
                'newTrip' => $newTrip,
                'tripStart' => $tripStart,
                'newTripStart' => $newTripStart,
            ];
            //dd($x, $tripStart, $newTripStart);
        }
        //dd(array_product($repetitions), $x, $trip_repetition, $tripStart, $newTrip_repetition, $newTripStart);
        $tripIntersectData['x'] = $x;
        return $tripIntersectData;
    }

    // A function for Chinese remainder Theorem

    // k is size of num[] and rem[].
    // Returns the smallest number x
    // such that:
    // x % num[0] = rem[0],
    // x % num[1] = rem[1],
    // ..................
    // x % num[k-2] = rem[k-1]
    // Assumption: Numbers in num[]
    // are pairwise co-prime (gcd for
    // every pair is 1)
    private function findMinX($num, $rem)
    {
        $x = 1; // Initialize result
        $k = sizeof($num);

        $max_k = array_product($num);
        // As per the Chinese remainder
        // theorem, this loop will
        // always break.
        while (true)
        {
            // Check if remainder of
            // x % num[j] is rem[j]
            // or not (for all j from
            // 0 to k-1)
            $j = 0;
            for ($j = 0; $j < $k; $j++ )
                if (($num[$j] != 0) && ($x % $num[$j] != $rem[$j]))
                break;

            // If all remainders
            // matched, we found x
            if ($j == $k)
                return $x;

            // Else try next number
            $x++;
            if($x > $max_k)
            {
                $x = -1;
                break;
            }
        }

        return $x;
    }
}
