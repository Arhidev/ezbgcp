<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repository\CouponRepositoryInterface;
use App\Repository\NotificationRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\CouponCustomerRepositoryInterface;
use App\Repository\TripSearchResultRepositoryInterface;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    private $couponRepository;
    private $messaging;
    private $userRepository;
    private $notificationRepository;
    private $couponCustomerRepository;
    private $tripSearchResultRepository;
    public function __construct(
        CouponRepositoryInterface $couponRepository,
        NotificationRepositoryInterface $notificationRepository,
        Messaging $messaging,
        UserRepositoryInterface $userRepository,
        CouponCustomerRepositoryInterface $couponCustomerRepository,
        TripSearchResultRepositoryInterface $tripSearchResultRepository)
    {
        $this->couponRepository = $couponRepository;
        $this->notificationRepository = $notificationRepository;
        $this->messaging = $messaging;
        $this->usersRepository = $userRepository;
        $this->couponCustomerRepository = $couponCustomerRepository;
        $this->tripSearchResultRepository = $tripSearchResultRepository;
    }

    //
    //index
    public function index()
    {
        //get all coupons
        return response()->json($this->couponRepository->all(), 200);
    }

    public function createEdit(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'coupon' => 'required',
            'coupon.id' => 'integer|nullable',
            'coupon.code' => 'required|string',
            'coupon.discount' => 'required|integer',
            'coupon.limit' => 'required|integer',
            'coupon.max_amount' => 'required|integer',
            'coupon.expiration_date' => 'required|date',
        ], [], []);

        $update = false;
        $coupon_id = null;
        if(array_key_exists('id', $request->coupon) && $request->coupon['id'] != null)
        {
            //update
            $update = true;
            $coupon_id = $request->coupon['id'];
        }

        //check if the coupon is already exists
        $coupon = $this->couponRepository->findByWhere(['code' => $request->coupon['code']])->first();
        if($coupon != null)
        {
            return response()->json(['message' => 'coupon already exists'], 400);
        }

        if($update)
        {
            //update the coupon data
            $this->couponRepository->update($coupon_id, $request->coupon);
            return response()->json(['success' => ['coupon updated successfully']]);
        }
        else
        {
            //create the coupon
            $this->couponRepository->create($request->coupon);
            return response()->json(['success' => ['coupon created successfully']]);
        }

    }

    //destroy
    public function destroy($coupon_id)
    {
        //delete the coupon
        $this->couponRepository->deleteById($coupon_id);
        return response()->json(['success' => ['coupon deleted successfully']]);
    }

    //notify
    public function notify(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'id' => 'required|integer',
            'message' => 'required|string',
        ], [], []);

        $coupon = $this->couponRepository->findById($request->id);
        if($coupon == null)
        {
            return response()->json(['error' => ['coupon not found']], 404);
        }

        //notify the users
        $customers = $this->usersRepository->all();
        $customerIds = $customers->pluck('id')->toArray();
        $tokens = $customers->pluck('fcm_token')->toArray();
        //save the notification for all customers
        // $notificationData = [];
        for ($i=0; $i < count($customerIds); $i++) {
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

    //applyCoupon for user on a planned trip
    public function applyCoupon(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'coupon_code' => 'required',
            'trip_search_result_id' => 'required|integer',
            'price' => 'required|numeric',
        ], [], []);

        $trip_search_result_id = $request->trip_search_result_id;

        $trip_search_result = $this->tripSearchResultRepository->findById($trip_search_result_id);

        $planned_trip_id = $trip_search_result->planned_trip_id;

        $user = Auth::user();
        $user_id = $user->id;

        //apply the coupon
        $coupon = $this->couponRepository->findByWhere(['code' => $request->coupon_code])->first();
        if($coupon == null)
        {
            return response()->json(['error' => 'coupon not found'], 404);
        }

        //check if the coupon is valid
        if($coupon->status == 1)
        {
            return response()->json(['error' => 'coupon is inactive'], 400);
        }

        //check if the coupon is expired
        if($coupon->expiration_date < date('Y-m-d H:i:s'))
        {
            return response()->json(['error' => 'coupon is expired'], 400);
        }

        //check if the coupon is used
        $couponsCustomer = $this->couponCustomerRepository->findByWhere(['coupon_id' => $coupon->id, 'user_id' => $user_id]);

        if(!$couponsCustomer->isEmpty())
        {
            //check if the coupon is used for the planned trip
            $usedForPlannedTrip = $couponsCustomer->where('planned_trip_id', $planned_trip_id);
            if(!$usedForPlannedTrip->isEmpty())
            {
                return response()->json(['error' => 'coupon is already used for the planned trip'], 400);
            }

            //check the limit
            if($coupon->limit > 0)
            {
                $usedCount = $couponsCustomer->count();
                if($usedCount >= $coupon->limit)
                {
                    return response()->json(['error' => 'coupon is already used the maximum times'], 400);
                }
            }
        }

        //calculate the discount
        $orgPrice = $request->price;
        $discount = $coupon->discount;
        $maxAmount = $coupon->max_amount;
        $discountAmount = $discount * $orgPrice / 100;
        if($maxAmount > 0 && $discountAmount > $maxAmount)
        {
            $discountAmount = $maxAmount;
        }
        if($discountAmount > $orgPrice)
        {
            $discountAmount = $orgPrice;
        }

        return response()->json(['success' => ['coupon applied successfully'], 'discount' => $discountAmount]);
    }

}
