<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\SettingRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserPaymentRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\RouteRepositoryInterface;
use App\Repository\StopRepositoryInterface;
use App\Repository\TripRepositoryInterface;
use App\Repository\UserRefundRepositoryInterface;
use App\Repository\PlannedTripRepositoryInterface;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{

    private $settingRepository;
    private $reservationRepository;
    private $userPaymentRepository;
    private $userRepository;
    private $routeRepository;
    private $stopRepository;
    private $tripRepository;
    private $userRefundRepository;
    private $planedTripRepository;

    public function __construct(
        SettingRepositoryInterface $settingRepository,
        ReservationRepositoryInterface $reservationRepository,
        UserPaymentRepositoryInterface $userPaymentRepository,
        UserRepositoryInterface $userRepository,
        RouteRepositoryInterface $routeRepository,
        StopRepositoryInterface $stopRepository,
        TripRepositoryInterface $tripRepository,
        UserRefundRepositoryInterface $userRefundRepository,
        PlannedTripRepositoryInterface $planedTripRepository)
    {
        $this->settingRepository = $settingRepository;
        $this->reservationRepository = $reservationRepository;
        $this->userPaymentRepository = $userPaymentRepository;
        $this->userRepository = $userRepository;
        $this->routeRepository = $routeRepository;
        $this->stopRepository = $stopRepository;
        $this->tripRepository = $tripRepository;
        $this->userRefundRepository = $userRefundRepository;
        $this->planedTripRepository = $planedTripRepository;
    }

    //thousands format
    private function thousandsFormat($num) {

        if($num>1000) {

              $x = round($num);
              $x_number_format = number_format($x);
              $x_array = explode(',', $x_number_format);
              $x_parts = array('k', 'm', 'b', 't');
              $x_count_parts = count($x_array) - 1;
              $x_display = $x;
              $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
              $x_display .= $x_parts[$x_count_parts - 1];

              return $x_display;

        }

        return $num;
      }

    // index
    public function index()
    {

        Log::info('DashboardController@index');

        //get the user
        $user = auth()->user();

        //count the following
        // Reservations
        // Customers
        // Drivers
        // Routes
        // Stops
        // Trips

        $reservationCount = $this->reservationRepository->all()->count();
        $allUsers = $this->userRepository->all();

        $customerCount = $allUsers->filter(function ($value, $key) {
            return $value->role == 1 && $value->status_id == 1;
        })->count();

        $driverCount = $allUsers->filter(function ($value, $key) {
            return $value->role == 2 && $value->status_id == 1;
        })->count();

        $routeCount = $this->routeRepository->all()->count();
        $stopCount = $this->stopRepository->all()->count();
        $tripCount = $this->tripRepository->all()->count();


        //get the current currency
        $currency = $this->settingRepository->all(['*'], ['currency'])->first();
        $currency_code = $currency->currency->code;

        //get total reservations
        $reservations = $this->reservationRepository->all(['*'], ['plannedTrip', 'plannedTrip.route', 'plannedTrip.trip']);
        //sum the paid prices of all reservations
        $totalReservationsAmount = $reservations->sum('paid_price');
        //approximate the total reservations amount to 2 decimal places
        $totalReservationsAmount = number_format($totalReservationsAmount, 2);

        //get all refunds
        $userRefunds = $this->userRefundRepository->all();
        //sum the paid prices of all refunds
        $totalRefundsAmount = $userRefunds->sum('amount');
        //approximate the total refunds amount to 2 decimal places
        $totalRefundsAmount = number_format($totalRefundsAmount, 2);

        //get all drivers payments
        $userPayments = $this->userPaymentRepository->all(['*'], ['user']);
        //filter the drivers payments to get only drivers
        $driversPayments = $userPayments->filter(function ($value, $key) {
            return $value->user->role == 2;
        });
        $adminPayments = $userPayments->filter(function ($value, $key) {
            return $value->user->role == 0;
        });


        //sum the paid prices of all drivers payments
        $totalDriversPayments = $driversPayments->sum('amount');
        $totalDriversPayments = number_format($totalDriversPayments, 2);

        //sum the paid prices of all admin payments
        $totalAdminPayments = $adminPayments->sum('amount');
        $totalAdminPayments = number_format($totalAdminPayments, 2);


        // get the best sales trips
        // group reservations by plannedTrip.trip.ID
        $reservations = $reservations->groupBy('plannedTrip.trip.id');
        //count the reservations of each trip
        $reservations = $reservations->map(function ($item, $key) {
            return $item->count();
        });
        //sort the trips by reservations count
        $reservations = $reservations->sortDesc();
        // get the top 5 trips
        $reservations = $reservations->take(5);
        $bestSalesTrips = [];
        foreach ($reservations as $key => $value) {
            $trip = $this->tripRepository->findById($key, ['*'], ['route']);
            $bestSalesTrips[] = [
                'id' => $trip->id,
                'route' => $trip->route->name,
                'time' => $trip->first_stop_time . ' - ' . $trip->last_stop_time,
                'repetition' => $this->getRepetitionPeriod($trip->repetition_period),
                'sales' => $this->thousandsFormat($value),
            ];
        }
        //check the length of bestSalesTrips array
        // if it is less than 5, then fill it with empty arrays
        if (count($bestSalesTrips) < 5) {
            $bestSalesTrips = [];
        }

        // //replicate bestSalesTrips array to 5 elements
        // if(count($bestSalesTrips) >0)
        // {
        //     $bestSalesTrips = array_pad($bestSalesTrips, 5, $bestSalesTrips[0]);
        // }




        $plannedTrips = $this->planedTripRepository->all();
        //group the planned trips by planned_date
        $plannedTrips = $plannedTrips->groupBy('planned_date');

        //get all planned trips with planned_date in a week from now
        $plannedTripsFutureWeek = $plannedTrips->filter(function ($value, $key) {
            return $value[0]->planned_date >= date('Y-m-d') && $value[0]->planned_date <= date('Y-m-d', strtotime('+7 days'));
        });

        $plannedTripsAll = $plannedTripsFutureWeek;
        //count the $plannedTripsFutureWeek. If it is less than 7, then fill it from the past
        if ($plannedTripsFutureWeek->count() < 7) {
            $remainingCount = 7 - $plannedTripsFutureWeek->count();

            //get only remainingCount planned trips from the past
            $plannedTripsPast = $plannedTrips->filter(function ($value, $key) {
                return $value[0]->planned_date < date('Y-m-d');
            });

            $plannedTripsPast = $plannedTripsPast->take($remainingCount);

            //merge the past and future planned trips
            $plannedTripsAll = $plannedTripsAll->toBase()->merge($plannedTripsPast);
        }

        //store only the count of each day in $plannedTripsAll
        $plannedTripsAll = $plannedTripsAll->map(function ($item, $key) {
            return $item->count();
        });

        //remove the year from the key
        $plannedTripsAll = $plannedTripsAll->mapWithKeys(function ($item, $key) {
            return [date('m-d', strtotime($key)) => $item];
        });

        //order the array by key
        $plannedTripsAll = $plannedTripsAll->sortKeys();

        if(count($plannedTripsAll) < 7) {
            $plannedTripsAll = [];
        }

        $dashboardStats = [
            'totalReservations' => $totalReservationsAmount . ' ' . $currency_code,
            'totalDriversEarnings' => $totalDriversPayments . ' ' . $currency_code,
            'totalAdminEarnings' => $totalAdminPayments . ' ' . $currency_code,
            'totalRefunds' => $totalRefundsAmount . ' ' . $currency_code,
            'totalReservationsCount' => $this->thousandsFormat($reservationCount),
            'totalCustomers' => $this->thousandsFormat($customerCount),
            'totalDrivers' => $this->thousandsFormat($driverCount),
            'totalRoutes' => $this->thousandsFormat($routeCount),
            'totalStops' => $this->thousandsFormat($stopCount),
            'totalTrips' => $this->thousandsFormat($tripCount),
            'bestTrips' => $bestSalesTrips,
            'plannedTrips' => $plannedTripsAll,
        ];

        return response()->json($dashboardStats, 200);
    }

    private function getRepetitionPeriod($period)
    {
        switch ($period) {
            case 0:
                return 'Once';
                break;
            case 1:
                return 'Daily';
                break;
            default:
                return 'Every ' . $period . ' days';
                break;
        }
    }
}
