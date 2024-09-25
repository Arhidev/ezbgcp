<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\ComplaintRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRefundRepositoryInterface;
use App\Traits\TripUtils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Validator;
class ComplaintController extends Controller
{

    use TripUtils;
    private $complaintRepository;
    private $userRepository;
    private $reservationRepository;
    private $userRefundRepository;

    public function __construct(
        ComplaintRepositoryInterface $complaintRepository,
        UserRepositoryInterface $userRepository,
        ReservationRepositoryInterface $reservationRepository,
        UserRefundRepositoryInterface $userRefundRepository)
    {
        $this->complaintRepository = $complaintRepository;
        $this->userRepository = $userRepository;
        $this->reservationRepository = $reservationRepository;
        $this->userRefundRepository = $userRefundRepository;
    }

    //index
    public function index()
    {
        $complaints = $this->complaintRepository->all(['*'], ['user', 'reservation']);

        // compute the distance between the user and the driver for each complaint
        foreach ($complaints as $complaint) {
            //compute the distance between the user and the stop
            $complaint->distanceToStop = $this->distance($complaint->stop_lat, $complaint->stop_lng, $complaint->customer_lat, $complaint->customer_lng);
            $complaint->distanceToStop = round($complaint->distanceToStop, 2) . ' km';
            //compute the distance between the user and the bus
            $complaint->distanceToBus = $this->distance($complaint->bus_lat, $complaint->bus_lng, $complaint->customer_lat, $complaint->customer_lng);
            $complaint->distanceToBus = round($complaint->distanceToBus, 2) . ' km';
            //compute the distance between the user and the driver
            $complaint->action = $complaint->status == 0 ? 'pending' : ($complaint->status == 1 ? 'refund' : 'cancel');
            //get the ticket number
            $complaint->ticket_number = $complaint->reservation->ticket_number;
            // compute the time difference between the planned time and the actual time
            $plannedTime = $complaint->planned_time != null ? strtotime($complaint->planned_time) : 0;
            $actualTime = $complaint->actual_time != null ? strtotime($complaint->actual_time) : 0;
            $reportedTime = $complaint->created_at != null ? strtotime($complaint->created_at) : 0;

            if($reportedTime == 0) {
                $complaint->timeDifferenceActual = '';
                $complaint->timeDifferencePlanned = '';
                continue;
            }

            if($actualTime == 0) {
                $complaint->timeDifferenceActual = '';
            }
            else
            {
                //compute the time difference
                $signActual = $actualTime > $reportedTime ? '-' : '+';
                if($signActual == '-') {
                    $complaint->timeDifferenceActual = $actualTime - $reportedTime;
                } else {
                    $complaint->timeDifferenceActual = $reportedTime - $actualTime;
                }
                //check if the time difference is more than one day
                $daysDifferenceActual = 0;
                if ($complaint->timeDifferenceActual > 86400) {
                    $daysDifferenceActual = floor($complaint->timeDifferenceActual / 86400);
                }
                //format the timestamp to days, hours, minutes and seconds
                $complaint->timeDifferenceActual = $signActual . ($daysDifferenceActual != 0? $daysDifferenceActual . ' days, ' : '') .gmdate("H:i:s", $complaint->timeDifferenceActual);
            }

            if($plannedTime == 0) {
                $complaint->timeDifferencePlanned = '';
                continue;
            }
            else {
                $signPlanned = $plannedTime > $reportedTime ? '-' : '+';
                if($signPlanned == '-') {
                    $complaint->timeDifferencePlanned = $plannedTime - $reportedTime;
                } else {
                    $complaint->timeDifferencePlanned = $reportedTime - $plannedTime;
                }
                //check if the time difference is more than one day
                $daysDifferencePlanned = 0;
                if ($complaint->timeDifferencePlanned > 86400) {
                    $daysDifferencePlanned = floor($complaint->timeDifferencePlanned / 86400);
                }
                $complaint->timeDifferencePlanned = $signPlanned. ($daysDifferencePlanned != 0? $daysDifferencePlanned . ' days, ' : '') .gmdate("H:i:s", $complaint->timeDifferencePlanned);
            }
        }

        //split into active and closed complaints
        $activeComplaints = $complaints->where('status', 0)->values();
        $closedComplaints = $complaints->where('status', '!=', 0)->values();

        //order $closedComplaints by updated_at
        $closedComplaints = $closedComplaints->sortByDesc('updated_at')->values();

        return response()->json(['active' => $activeComplaints, 'completed' => $closedComplaints], 200);
    }

    //create complaint
    public function create(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|integer',
            'complaint' => 'required|string',
            'customer_lat' => 'required|numeric',
            'customer_lng' => 'required|numeric',
        ]);

        //check if the auth user is customer
        $user = Auth::user();
        if ($user->role != 1) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $complaintData = $request->all();

        //get the stop name, lat and lng from the reservation
        $reservation = $this->reservationRepository->findByWhere(['id' => $request->reservation_id, 'user_id' => $user->id], ['*'], ['firstStop', 'plannedTrip.plannedTripDetail'])->first();

        if ($reservation == null) {
            return response()->json(['errors' => 'Reservation not found'], 404);
        }

        //0 not ride, 1-ride, 2-miss ride, 3-drop off, 4 - cancelled by admin

        // if ($reservation->ride_status == 0) {
        //     return response()->json(['errors' => 'You did not ride the bus'], 400);
        // }

        //check if the user already ride
        if ($reservation->ride_status >= 1 && $reservation->ride_status <= 3) {
            return response()->json(['errors' => 'You already used this reservation'], 400);
        }

        //check if the admin cancelled the ride
        if ($reservation->ride_status == 4) {
            return response()->json(['errors' => 'The ride is already cancelled by the admin'], 400);
        }

        //check if the customer is already make a complaint for this reservation
        $complaint = $this->complaintRepository->findByWhere(['reservation_id' => $request->reservation_id, 'user_id' => $user->id])->first();
        if ($complaint != null) {
            return response()->json(['errors' => 'You already make a complaint for this reservation. Please wait for the admin to take action'], 400);
        }

        $stopID = $reservation->start_stop_id;
        $complaintData['user_id'] = $user->id;
        $complaintData['stop_id'] = $stopID;
        $complaintData['stop_name'] = $reservation->firstStop->name;
        $complaintData['stop_lat'] = $reservation->firstStop->lat;
        $complaintData['stop_lng'] = $reservation->firstStop->lng;

        //get the planned time
        //loop through the planned trip details to get the planned time of the stop
        foreach ($reservation->plannedTrip->plannedTripDetail as $plannedDetail) {
            if ($plannedDetail->stop_id == $stopID) {
                $complaintData['planned_time'] = $reservation->plannedTrip->planned_date . ' ' . $plannedDetail->planned_timestamp;
                if($plannedDetail->actual_timestamp != null)
                {
                    $complaintData['actual_time'] = $reservation->plannedTrip->planned_date . ' ' . 
                    $plannedDetail->actual_timestamp;
                }
                break;
            }
        }


        //get the last reported location of the bus
        $complaintData['bus_lat'] = $reservation->plannedTrip->last_position_lat;
        $complaintData['bus_lng'] = $reservation->plannedTrip->last_position_lng;

        $complaint = $this->complaintRepository->create($complaintData);

        return response()->json($complaint, 200);
    }

    //takeAction
    public function takeAction(Request $request)
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'complaint_id' => 'required|integer',
            'action' => 'required|string',
            'response' => 'required|string',
        ]);
        
        //check if validator fails
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
        //find the complaint
        $complaint = $this->complaintRepository->findById($request->complaint_id);

        if ($complaint == null) {
            return response()->json(['message' => 'Complaint not found'], 404);
        }
        //set the action to 1-refund, 2-cancel
        $action = 0;
        if ($request->action == "refund") {
            $action = 1;
        } else if ($request->action == "cancel") {
            $action = 2;
        } else {
            return response()->json(['message' => 'Invalid action'], 400);
        }

        DB::beginTransaction();
        try {
            $complaint->status = $action;
            $complaint->response = $request->response;
            $complaint->save();
    
            //record the refund data
            if ($action == 1) {
                $this->userRefundRepository->create([
                    'user_id' => $complaint->user_id,
                    'reservation_id' => $complaint->reservation_id,
                    'amount' => $complaint->reservation->paid_price,
                    'refund_date' => date('Y-m-d'),
                    'reason' => "Complaint refund"
                ]);

                //change the reservation status to cancelled ride_status = 4
                $this->reservationRepository->update($complaint->reservation_id, ['ride_status' => 4]);

                //add the amount to the customer wallet
                $customer = $this->userRepository->findById($complaint->user_id);
                $customer->wallet += $complaint->reservation->paid_price;
                $customer->save();
            }

            DB::commit();
            return response()->json(['message' => 'Complaint updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }


    }
}
