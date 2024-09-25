<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRefundRepositoryInterface;
use App\Repository\UserPaymentRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\SettingRepositoryInterface;
use App\Repository\CouponCustomerRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    private $reservationRepository;
    private $userRefundRepository;
    private $userPaymentRepository;
    private $userRepository;
    private $settingRepository;
    private $couponCustomerRepository;

    public function __construct(
        ReservationRepositoryInterface $reservationRepository,
        UserRefundRepositoryInterface $userRefundRepository,
        UserPaymentRepositoryInterface $userPaymentRepository,
        UserRepositoryInterface $userRepository,
        SettingRepositoryInterface $settingRepository,
        CouponCustomerRepositoryInterface $couponCustomerRepository)
    {
        $this->reservationRepository = $reservationRepository;
        $this->userRefundRepository = $userRefundRepository;
        $this->userPaymentRepository = $userPaymentRepository;
        $this->userRepository = $userRepository;
        $this->settingRepository = $settingRepository;
        $this->couponCustomerRepository = $couponCustomerRepository;
    }

    public function index()
    {
        $reservation = $this->reservationRepository->all(
            ['*'], ['firstStop', 'lastStop', 'plannedTrip.route', 'customer', 'plannedTrip.driver']);

        //filter reservations by ride_status

        $activeReservations = [];
        $rideReservations = [];
        $missedReservations = [];
        $completedReservations = [];
        $cancelledReservations = [];

        foreach ($reservation as $res) {
            //if the ride_status is 0, then it is active
            if ($res->ride_status == 0) {
                array_push($activeReservations, $res);
            } else if ($res->ride_status == 1) {
                //if the ride_status is 1, then it is ride (on-road)
                array_push($rideReservations, $res);
            } else if ($res->ride_status == 2) {
                //if the ride_status is 2, then it is missed
                array_push($missedReservations, $res);
            } else if ($res->ride_status == 3) {
                //if the ride_status is 3, then it is completed
                array_push($completedReservations, $res);
            }
            else if ($res->ride_status == 4) {
                //if the ride_status is 4, then it is cancelled
                array_push($cancelledReservations, $res);
            }

        }

        $reservations = [
            'active' => $activeReservations,
            'ride' => $rideReservations,
            'missed' => $missedReservations,
            'completed' => $completedReservations,
            'cancelled' => $cancelledReservations
        ];

        return response()->json($reservations, 200);
    }

    //cancel
    public function cancel(Request $request)
    {
        //validate
        $request->validate([
            'id' => 'required|integer',
            'reason' => 'required|string',
        ]);

        $reservation = $this->reservationRepository->findById($request->id, ['*'], ['customer', 'plannedTrip.driver']);

        if ($reservation) {

            if ($reservation->ride_status == 4) {
                return response()->json(['message' => 'Reservation already cancelled'], 400);
            }

            // // This is optional, but it's a good idea to check if the customer already used the ride
            // //make sure that the ride_status is 0
            // if ($reservation->ride_status != 0) {
            //     return response()->json(['message' => 'Reservation cannot be cancelled'], 400);
            // }

            $reservation_ride_status = $reservation->ride_status;

            //make transaction, set ride_status to 4 and deduct from wallet and add to admin wallet
            DB::beginTransaction();
            try {
                $customer = $reservation->customer;
                $customer->wallet += $reservation->paid_price;
                $customer->save();

                $plannedTrip = $reservation->plannedTrip;
                $couponsCustomer = $this->couponCustomerRepository->findByWhere(['user_id' => $customer->id, 'planned_trip_id' => $plannedTrip->id]);
                if(!$couponsCustomer->isEmpty())
                {
                    $couponCustomer = $couponsCustomer->first();
                    $couponCustomer->delete();
                }

                $reservation->ride_status = 4;
                $reservation->save();

                $returnedSuccessMessage = 'Reservation cancelled successfully';
                $adminRedeem = false;
                $driverRedeem = false;
                if($reservation_ride_status >= 1 && $reservation_ride_status <= 3)
                {
                    //deduct from admin wallet
                    $admin = $this->userRepository->allWhere(['*'], [], ['role' => 0], false)->first();
                    $admin->wallet -= $reservation->admin_share;
                    $admin->save();

                    //deduct from driver wallet
                    $driver = $reservation->plannedTrip->driver;
                    $driver->wallet -= $reservation->driver_share;
                    $driver->save();

                    $reservationPayments = $this->userPaymentRepository->allWhere(['*'], [], ['reservation_id' => $reservation->id], false);

                    foreach ($reservationPayments as $payment) {
                        if($payment->user_id == $admin->id)
                        {
                            if($payment->redeemed == 1)
                            {
                                $adminRedeem = true;
                            }
                        }
                        else if($payment->user_id == $driver->id)
                        {
                            if($payment->redeemed == 1)
                            {
                                $driverRedeem = true;
                            }
                        }
                    }
                    //delete from $this->userPaymentRepository
                    $this->userPaymentRepository->deleteWhere(['reservation_id' => $reservation->id]);

                    //get the current currency
                    $currency = $this->settingRepository->all(['*'], ['currency'])->first();
                    $currency_code = $currency->currency->code;

                    if($adminRedeem)
                    {
                        $returnedSuccessMessage = $returnedSuccessMessage . '. Please note that the admin payment has been redeemed. Please contact the payment provider of the admin to refund the admin payment of ' . round($reservation->admin_share, 2) . ' ' .
                        $currency_code;
                    }
                    if($driverRedeem)
                    {
                        $returnedSuccessMessage = $returnedSuccessMessage . '. Please note that the driver payment has been redeemed. Please contact the payment provider of the driver to refund the driver payment of ' . round($reservation->driver_share, 2) . ' ' . $currency_code;
                    }
                }

                $this->userRefundRepository->create([
                    'user_id' => $customer->id,
                    'amount' => $reservation->paid_price,
                    'reason' => $request->reason,
                    'reservation_id' => $reservation->id,
                    'refund_date' => date('Y-m-d'),
                ]);

                DB::commit();
                return response()->json(['message' => $returnedSuccessMessage], $adminRedeem || $driverRedeem ? 201 : 200);
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                return response()->json(['message' => 'Reservation not cancelled'], 500);
            }
        } else {
            return response()->json(['message' => 'Reservation not found'], 404);
        }
    }

    //getReservationDetails
    public function getReservationDetails($id)
    {
        $reservation = $this->reservationRepository->findById($id, ['*'], ['plannedTrip.driver.driverInformation', 'plannedTrip.route.stops',
        'plannedTrip.route.routeStops.routeStopDirections']);
        if ($reservation) {
            $route = $reservation->plannedTrip->route;
            //decode the overview_path for each route direction
            $directions = [];
            $distance = 0;
            foreach ($route->routeStops as $routeStop) {
                if(count($routeStop->routeStopDirections) > 0) {
                    $routeDirections = [];
                    foreach ($routeStop->routeStopDirections as $key => $route_stop_direction) {
                        $path = json_decode($route_stop_direction->overview_path);
                        $d = [
                            'summary' => $route_stop_direction->summary,
                            'current' => $route_stop_direction->current,
                            'index' => $route_stop_direction->index,
                            'overview_path' => $path
                        ];
                        array_push($routeDirections, $d);
                    }
                    array_push($directions, $routeDirections);
                }
            }
            $route->directions = $directions;
            $reservation->route_details = $route;
            return response()->json(['reservation' => $reservation], 200);
        } else {
            return response()->json(['message' => 'Reservation not found'], 404);
        }
    }
}
