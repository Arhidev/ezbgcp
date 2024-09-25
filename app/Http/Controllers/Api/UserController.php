<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\UserRepositoryInterface;
use App\Repository\UserChargeRepositoryInterface;
use App\Repository\UserPaymentRepositoryInterface;
use App\Repository\RedemptionRepositoryInterface;
use App\Repository\SettingRepositoryInterface;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Razorpay\Api\Api;
use App\Traits\UserUtils;
use EdwardMuss\Rave\Facades\Rave as Flutterwave;
use App\Models\FlutterwaveTransaction;
use App\Models\PaytabsTransaction;
use Paytabscom\Laravel_paytabs\Facades\paypage;
class UserController extends Controller
{
    use UserUtils;
    //
    private $auth;
    private $userRepository;
    private $UserChargeRepository;
    private $userPaymentRepository;
    private $redemptionRepository;
    private $settingRepository;
    public function __construct(
        Auth $auth,
        UserRepositoryInterface $userRepository,
        UserChargeRepositoryInterface $UserChargeRepository,
        UserPaymentRepositoryInterface $userPaymentRepository,
        RedemptionRepositoryInterface $redemptionRepository,
        SettingRepositoryInterface $settingRepository
    ) {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
        $this->UserChargeRepository = $UserChargeRepository;
        $this->userPaymentRepository = $userPaymentRepository;
        $this->redemptionRepository = $redemptionRepository;
        $this->settingRepository = $settingRepository;
    }

    public function index(Request $request)
    {
        $this->validate($request, [
            'userType' => [
                'required',
                Rule::in(['admin', 'customers', 'drivers']),
            ],
        ], [], []);
        $role = 0; //0, admin, 1 customer, 2 driver
        $with = [];
        switch ($request->userType) {
            case 'admin':
                $role = 0;
                break;
            case 'customers':
                $role = 1;
                break;
            case 'drivers':
                $role = 2;
                $with = ['bus'];
                break;
        }

        $allUsers = $this->userRepository->allWhere(['*'], $with, [['role', '=', $role]]);

        return response()->json($allUsers, 200);
    }

    public function getUser($user_id)
    {
        //get the current currency
        $currency = $this->settingRepository->all(['*'], ['currency'])->first();
        $currency_code = $currency->currency->code;

        $user = $this->userRepository->findById($user_id, ['*'], ['bus', 'driverInformation.documents']);
        //approximate wallet
        $user->wallet = number_format($user->wallet, 2, '.', '');
        return response()->json($user, 200);
    }

    public function suspendActivate(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'user_id' => 'required|integer',
        ], [], []);

        $user_id = $request->user_id;

        $user = $this->userRepository->findById($user_id);
        $user->status_id = $user->status_id != 1 ? 1 : 3;
        $this->userRepository->update($user_id, $user->toArray());
        return response()->json(['success' => ['user updated successfully']]);
    }

    public function upload_user_photo(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'avatar' => 'required',
        ], [], []);

        $user = $request->user();
        $user_id = $user->id;
        //get user
        $user = $this->userRepository->findById($user_id);
        // check if image has been received from form
        if ($request->file('avatar')) {
            Log::info('file');
            $imageName = time().'.'.$request->avatar->getClientOriginalExtension();
            $storagePath = Storage::url('avatars/'. $user_id);
            $imageAbsolutePath = public_path('/backend'.$storagePath);
            $request->avatar->move($imageAbsolutePath, $imageName);

            // Update user's avatar column on 'users' table
            $user->avatar = $storagePath .'/' . $imageName;

            if ($user->save()) {
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'Profile Photo Updated!',
                    'avatar_url' =>  $user->avatar
                ]);
            } else {
                return response()->json([
                    'status'    => 'failure',
                    'message'   => 'failed to update profile photo!',
                    'avatar_url' => NULL
                ], 400);
            }
        }
        else
        {
            $image = $request->avatar;
            try{
                $image = str_replace('data:image/png;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $image = base64_decode($image);

                $imageName = time().'.png';
                $storagePath = Storage::url('avatars/'. $user_id);
                $imageAbsolutePath = public_path('/backend'.$storagePath);
                file_put_contents($imageAbsolutePath.'/'.$imageName, $image);

                // Update user's avatar column on 'users' table
                $user->avatar = $storagePath .'/' . $imageName;
                $user->save();

                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'Profile Photo Updated!',
                    'avatar_url' =>  $user->avatar
                ]);
            }
            catch(\Exception $e)
            {
                Log::info($e->getMessage());
                return response()->json([
                    'status'    => 'failure',
                    'errors'   => 'No image file uploaded!',
                    'avatar_url' => NULL
                ], 400);
            }
        }
    }


    public function changePassword(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'user_id' => 'required|integer',
            'password' => 'required',
        ], [], []);
        $user_id = $request->user_id;
        //get user
        $user = $this->userRepository->findById($user_id);

        //check if uid is set
        if($user->uid == null)
        {
            return response()->json(['message' => 'User does not have a password'], 403);
        }

        $password = $request->password;

        //update the password in auth
        $this->auth->changeUserPassword($user->uid, $password);
        return response()->json(['success' => ['user updated successfully']]);
    }


    public function Edit(Request $request)
    {
        $this->validate($request, [
            'user' => 'required',
            'user.id' => 'integer|required',
            'user.name' => 'required|string',
            'user.email' => 'required|email',
            'user.tel_number' => 'numeric|nullable',
            'user.wallet' => 'numeric|nullable',
            'user.status_id' => ['nullable', Rule::in([1, 2, 3])], //1, active, 2 pending, 3 suspended
        ], [], []);

        //get user
        $user_id = $request->user['id'];
        $user = $this->userRepository->findById($user_id);
        $user->name = $request->user['name'];
        $user->email = $request->user['email'];
        if(array_key_exists('tel_number', $request->user))
        {
            $user->tel_number = $request->user['tel_number'];
        }
        if(array_key_exists('wallet', $request->user))
        {
            $user->wallet = $request->user['wallet'];
        }
        DB::beginTransaction();
        try {
            //update the user in firebase
            $this->auth->updateUser($user->uid, [
                'displayName' => $user->name,
                'email' => $user->email,
            ]);
            if($user->role == 0)  //update the admin user
            {
                //only allowed changes are wallet, name, email, tel_number for admins
                $this->userRepository->update($user_id, $user->toArray());

            }
            else{
                //can update status
                if(array_key_exists('status_id', $request->user))
                {
                    $user->status_id = $request->user['status_id'];
                }
                $this->userRepository->update($user_id, $user->toArray());
            }
            DB::commit();
            return response()->json(['success' => ['user updated successfully']]);
        }
        catch (UserNotFound $e) {
            DB::rollback();
            return response()->json(['message' => 'Firebase: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'User update failed'], 500);
        }
    }

    public function getDevices(Request $request)
    {
        Log::info('request: getDevices11');
        // get the required user
        $user = $request->user();
        Log::info('user: '.$user);
        //if the user found
        if ($user) {
            return response()->json(['devices' => $user->tokens()->select('id', 'name', 'last_used_at')->get()]);
        }
        else
        {
            return response()->json(['errors' => ['User' => ['user does not exist']]], 403);
        }
    }

    //getReservations
    public function getReservations(Request $request)
    {
        Log::info('request: getReservations');
        // get the required user
        $user = $request->user();
        //if the user found
        if ($user) {
            $reservations = $user->reservations()->with(['firstStop', 'plannedTrip.route'])->get();

            $reservations = $reservations->sortBy(function($reservation, $key) {
                return $reservation->plannedTrip->planned_date;
              });

            $reservations = $reservations->values()->all();

            return response()->json(['reservations' => $reservations]);
        }
        else
        {
            return response()->json(['errors' => ['User' => ['user does not exist']]], 403);
        }
    }
    //captureRazorpayPayment
    public function captureRazorpayPayment(Request $request)
    {
        //validate the request paymentId
        $this->validate($request, [
            'paymentId' => 'required|string',
        ], [], []);

        $paymentId = $request->paymentId;
        $api_key = env('RAZORPAY_KEY');
        $api_secret = env('RAZORPAY_SECRET');
        $razorPayApi = new Api($api_key, $api_secret);
        $user = $request->user();

        //fetch the payment
        $payment = $razorPayApi->payment->fetch($paymentId);
        if ($payment) {
            DB::beginTransaction();
            try {
                $amount = $payment->amount;
                //capture the payment
                $paymentResponse = $payment->capture(array('amount' => $amount));
                Log::info('paymentResponse: '. json_encode($paymentResponse));
                //check if paymentResponse contains error
                if(isset($paymentResponse->error))
                {
                    Log::info('paymentResponse: '.json_encode($paymentResponse));
                    return response()->json(['message' => $paymentResponse->error->description], 422);
                }
                $paymentMethod = 1;
                //add user charge
                $userCharge = [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'payment_date' => Carbon::now(),
                    'payment_method' => $paymentMethod
                ];
                $this->UserChargeRepository->create($userCharge);

                //add wallet
                $user->wallet += $amount;
                $this->userRepository->update($user->id, $user->toArray());

                $payments = $this->UserChargeRepository->allWhere(['*'], [], [['user_id', '=', $user->id]], true);
                foreach ($payments as $payment) {
                    $payment->payment_method = $payment->payment_method == 1 ? 'card':'paypal';
                }
                DB::commit();

                $paymentMethod = $this->getPaymentMethod();
                //get the current currency
                $setting = $this->settingRepository->all(['*'], ['currency'])->first();
                $currency_code = $setting->currency->code;

                return response()->json([
                    'success' => true,
                    'payments' => $payments,
                    'wallet_balance' => $user->wallet,
                    'currency' => $currency_code,
                    'payment_method' => $paymentMethod
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }
        else
        {
            return response()->json(['message' => 'Payment not found'], 404);
        }
    }

    //verifyTransaction
    public function verifyTransaction(Request $request)
    {
        //validate the request transactionId
        $this->validate($request, [
            'transactionId' => 'required|string',
        ], [], []);

        $transactionId = $request->transactionId;
        $user = $request->user();


        //check if the transactionId is in the flutterwave_transactions table
        $flutterwaveTransaction = FlutterwaveTransaction::where('transaction_id', $transactionId)->first();
        if($flutterwaveTransaction != null)
        {
            return response()->json(['message' => 'Payment already captured'], 422);
        }
        //fetch the payment
        $verificationResponse = Flutterwave::verifyTransaction($transactionId);
        if ($verificationResponse) {
            Log::info('verificationResponse: '. json_encode($verificationResponse));
            DB::beginTransaction();
            try {
                //store the transactionId in the flutterwave_transactions table
                $flutterwaveTransaction = new FlutterwaveTransaction();
                $flutterwaveTransaction->transaction_id = $transactionId;
                $flutterwaveTransaction->save();
                // get amount_settled from the verification response
                $amount = $verificationResponse["data"]["amount_settled"];
                $paymentMethod = 1;
                //add user charge
                $userCharge = [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'payment_date' => Carbon::now(),
                    'payment_method' => $paymentMethod
                ];
                $this->UserChargeRepository->create($userCharge);

                //add wallet
                $user->wallet += $amount;
                $this->userRepository->update($user->id, $user->toArray());

                $payments = $this->UserChargeRepository->allWhere(['*'], [], [['user_id', '=', $user->id]], true);
                foreach ($payments as $payment) {
                    $payment->payment_method = $payment->payment_method == 1 ? 'card':'paypal';
                }
                DB::commit();

                $paymentMethod = $this->getPaymentMethod();
                //get the current currency
                $setting = $this->settingRepository->all(['*'], ['currency'])->first();
                $currency_code = $setting->currency->code;

                return response()->json([
                    'success' => true,
                    'payments' => $payments,
                    'wallet_balance' => $user->wallet,
                    'currency' => $currency_code,
                    'payment_method' => $paymentMethod
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }
        else
        {
            return response()->json(['message' => 'Payment not found'], 404);
        }
    }

    //capturePaytabsPayment
    public function capturePaytabsPayment(Request $request)
    {
        //validate the request paymentId
        $this->validate($request, [
            'tran_ref' => 'required|string',
        ], [], []);

        $tranRef = $request->tran_ref;
        $user = $request->user();

        //check if the transactionId is in the flutterwave_transactions table
        $paytabsTransaction = PaytabsTransaction::where('transaction_id', $tranRef)->first();
        if($paytabsTransaction != null)
        {
            return response()->json(['message' => 'Payment already captured'], 422);
        }
        //fetch the transaction
        $transaction = Paypage::queryTransaction($tranRef);
        Log::info('transaction: '. json_encode($transaction));

        //check if the transaction is found
        if ($transaction == null) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        //check if the transaction is sale (lower case)
        if(strtolower($transaction->tran_type) != 'sale')
        {
            return response()->json(['message' => 'Payment not in sale status. It has ' . $transaction->tran_type . " status"], 422);
        }

        if ($transaction) {
            DB::beginTransaction();
            try {

                //store the transactionId in the PaytabsTransaction table
                $paytabsTransaction = new PaytabsTransaction();
                $paytabsTransaction->transaction_id = $tranRef;
                $paytabsTransaction->save();

                $amount = $transaction->tran_total;
                $paymentMethod = 1;
                //add user charge
                $userCharge = [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'payment_date' => Carbon::now(),
                    'payment_method' => $paymentMethod
                ];
                $this->UserChargeRepository->create($userCharge);

                //add wallet
                $user->wallet += $amount;
                $this->userRepository->update($user->id, $user->toArray());

                $payments = $this->UserChargeRepository->allWhere(['*'], [], [['user_id', '=', $user->id]], true);
                foreach ($payments as $payment) {
                    $payment->payment_method = $payment->payment_method == 1 ? 'card':'paypal';
                }
                DB::commit();

                $paymentMethod = $this->getPaymentMethod();
                //get the current currency
                $setting = $this->settingRepository->all(['*'], ['currency'])->first();
                $currency_code = $setting->currency->code;

                return response()->json([
                    'success' => true,
                    'payments' => $payments,
                    'wallet_balance' => $user->wallet,
                    'currency' => $currency_code,
                    'payment_method' => $paymentMethod
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }
        else
        {
            return response()->json(['message' => 'Payment not found'], 404);
        }
    }

    //payNonce
    public function payNonce(Request $request)
    {
        //validate the request
        $validator = $this->validate($request, [
            'nonce' => 'required|string',
            'amount' => 'required|numeric',
        ], [] , []);

        $user = $request->user();
        $nonce = $request->nonce;
        $amount = $request->amount;


        $gateway = new \Braintree\Gateway([
            'environment' => env('BRAINTREE_ENV'),
            'merchantId' => env('BRAINTREE_MERCHANT_ID'),
            'publicKey' => env('BRAINTREE_PUBLIC_KEY'),
            'privateKey' => env('BRAINTREE_PRIVATE_KEY')
            ]);

        Log::info('nonce = '.$nonce . ' amount = '.$amount);
        //
        DB::beginTransaction();
        try {
            $paymentMethodNonce = $gateway->paymentMethodNonce()->find($nonce);
            Log::info(json_encode($paymentMethodNonce));
            //unassign driver from bus first
            $status =  $gateway->transaction()->sale(
                [
                    'amount' => $amount,
                    'paymentMethodNonce' => $nonce,
                    'options' => [
                        'submitForSettlement' => True
                    ]
                ]
            );
            //check $status
            if (!$status->success) {
                Log::info('status: '.$status);
                Log::info('status: '.$status->message);
                return response()->json(['message' => $status->message], 422);
            }

            Log::info($paymentMethodNonce->type);
            Log::info(str_contains($paymentMethodNonce->type, 'PayPal'));
            $paymentMethod = 1;
            // check if the payment method contains the word 'PayPal'
            if(str_contains($paymentMethodNonce->type, 'PayPal')) {
                $paymentMethod = 2;
            }
            //add user charge
            $userCharge = [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_date' => Carbon::now(),
                'payment_method' => $paymentMethod
            ];
            $this->UserChargeRepository->create($userCharge);

            //add wallet
            $user->wallet += $amount;
            $this->userRepository->update($user->id, $user->toArray());

            $payments = $this->UserChargeRepository->allWhere(['*'], [], [['user_id', '=', $user->id]], true);
            foreach ($payments as $payment) {
                $payment->payment_method = $payment->payment_method == 1 ? 'card':'paypal';
            }
            DB::commit();

            $paymentMethod = $this->getPaymentMethod();
            //get the current currency
            $setting = $this->settingRepository->all(['*'], ['currency'])->first();
            $currency_code = $setting->currency->code;

            return response()->json([
                'success' => true,
                'payments' => $payments,
                'wallet_balance' => $user->wallet,
                'currency' => $currency_code,
                'payment_method' => $paymentMethod
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json($status);
    }
    //payments
    public function getWalletCharges(Request $request)
    {
        Log::info('request: getWalletCharges');
        // get the required user
        $user = $request->user();
        //if the user found
        if ($user) {
            $payments = $user->charges()->get();
            foreach ($payments as $payment) {
                $payment->payment_method = $payment->payment_method == 1 ? 'card':'paypal';
            }
            $paymentMethod = $this->getPaymentMethod();
            //get the current currency
            $setting = $this->settingRepository->all(['*'], ['currency'])->first();
            $currency_code = $setting->currency->code;
            //ads
            $allow_ads_in_customer_app = $setting->allow_ads_in_customer_app;
            $allow_ads_in_driver_app = $setting->allow_ads_in_driver_app;

            Log::info('payments currency_code: '.$currency_code);
            return response()->json(['success' => true,
                'payments' => $payments,
                'wallet_balance' => $user->wallet,
                'currency' => $currency_code,
                'payment_method' => $paymentMethod,
                'allow_ads_in_customer_app' => $allow_ads_in_customer_app,
                'allow_ads_in_driver_app' => $allow_ads_in_driver_app
            ], 200);
        }
        else
        {
            return response()->json(['errors' => ['User' => ['user does not exist']]], 403);
        }
    }


    //redeem
    public function redeem(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'user_id' => 'required|integer',
        ], [], []);

        $user_id = $request->user_id;
        $user = $this->userRepository->findById($user_id);

        //check if the user is customer
        if($user->role == 1)
        {
            return response()->json(['message' => 'You are not allowed to redeem'], 403);
        }

        //make transaction, deduct from wallet and add to admin wallet
        DB::beginTransaction();
        try {
            //mark all payments as redeemed
            //bulk update the redeemed payments to true
            $this->userPaymentRepository->bulkUpdate(['redeemed' => true], [['user_id', '=', $user->id], ['redeemed', '=', false]]);

            $redemptionData = [
                'user_id' => $user->id,
                'redemption_amount' => $user->wallet,
            ];

            //get the preferred redemption method
            $userRedemptionMethod = $user->redemption_preference;
            if($userRedemptionMethod == 1) //cash
            {
                $redemptionData['redemption_type_id'] = 1;
            }
            else if($userRedemptionMethod == 2) //bank
            {
                $redemptionData['redemption_type_id'] = 2;
                $redemptionData['bank_account_id'] = $user->bank_account_id;
            }
            else if($userRedemptionMethod == 3) //paypal
            {
                $redemptionData['redemption_type_id'] = 3;
                $redemptionData['paypal_account_id'] = $user->paypal_account_id;
            }
            else if($userRedemptionMethod == 4) //mobile money
            {
                $redemptionData['redemption_type_id'] = 4;
                $redemptionData['mobile_money_account_id'] = $user->mobile_money_account_id;
            }

            $this->redemptionRepository->create($redemptionData);

            $user->wallet = 0;
            $user->save();

            DB::commit();
            return response()->json(['message' => 'Redeemed successfully'], 200);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Redeem failed'], 500);
        }
    }

    //getUpcomingPayments
    public function getUpcomingPayments(Request $request)
    {
        //group by user_id
        $payments = $this->userPaymentRepository->allWhere(['*'], ['user.bankAccount', 'user.mobileMoneyAccount', 'user.paypalAccount'],
         [['redeemed', '=', false]], true);
        $payments = $payments->groupBy(function ($item, $key) {
            return $item->user_id;
        });

        //order by user_id
        $payments = $payments->sortBy(function($payment, $key) {
            return $key;
          });

        //sum amount per each user and number of reservations
        $payments  = $payments->map(function ($item, $key) {
            $totalAmount = $item->sum('amount');
            $totalReservations = $item->count();
            $user = $item->first()->user;
            $redemption_preference = $user->redemption_preference;
            if($redemption_preference == 2)
            {
                $redemption_details = $user->bankAccount;
            }
            else if($redemption_preference == 3)
            {
                $redemption_details = $user->paypalAccount;
            }
            else if($redemption_preference == 4)
            {
                $redemption_details = $user->mobileMoneyAccount;
            }
            else
            {
                $redemption_details = null;
            }

            //check if user is customer
            if($user->role == 1)
            {
                return null;
            }
            return [
                'id' => $key,
                'user_name' => $user->name,
                'redemption_preference' => $redemption_preference,
                'redemption_details' => $redemption_details,
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'total_reservations' => $totalReservations
            ];
        });

        return response()->json($payments->values()->all(), 200);
    }

    //getPaymentDetails
    public function getPaymentDetails(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'user_id' => 'required|integer',
        ], [], []);

        $user_id = $request->user_id;
        Log::info('user_id: '.$user_id);
        $user = $this->userRepository->findById($user_id);

        //check if the user is customer
        if($user->role == 1)
        {
            return response()->json(['message' => 'You are not allowed to get the payment details'], 403);
        }

        //get the payments
        $payments = $this->userPaymentRepository->findByWhere([['user_id', '=', $user->id], ['redeemed', '=', false]], ['*'], ['reservation.plannedTrip.route', 'reservation.firstStop', 'reservation.lastStop']);

        $payments = $payments->sortBy(function($payment, $key) {
            return $payment->reservation->plannedTrip->planned_date;
          });

        //approximate the amount
        $payments = $payments->map(function ($item, $key) {
            $item->amount = number_format($item->amount, 2, '.', '');
            return $item;
        });

        $payments = $payments->values()->all();

        return response()->json([
            'payment_details' => $payments,
            'user_name' => $user->name,
        ], 200);
    }

    //getRedemptions
    public function getRedemptions(Request $request)
    {
        //group by user_id
        $redemptions = $this->redemptionRepository->allWhere(['*'], ['user', 'bankAccount', 'mobileMoneyAccount', 'paypalAccount'], [], true);

        $redemptions  = $redemptions->map(function ($item, $key) {

            $user = $item->user;
            $redemption_preference = 1;
            if($item->bank_account_id != null)
            {
                //get the bank account
                $redemption_details = $item->bankAccount;
                $redemption_preference = 2;
            }
            else if($item->paypal_account_id != null)
            {
                //get the paypal account
                $redemption_details = $item->paypalAccount;
                $redemption_preference = 3;
            }
            else if($item->mobile_money_account_id != null)
            {
                //get the mobile money account
                $redemption_details = $item->mobileMoneyAccount;
                $redemption_preference = 4;
            }
            else
            {
                $redemption_details = null;
            }
            return [
                'id' => $key,
                'user_name' => $user->name,
                'user_id' => $user->id,
                'redemption_amount' => number_format($item->redemption_amount, 2, '.', ''),
                'redemption_preference' => $redemption_preference,
                'redemption_details' => $redemption_details,
                'date' => $item->created_at,
            ];
        });

        return response()->json($redemptions->values()->all(), 200);
    }

    //updateProfile
    public function updateProfile(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'tel_number' => 'string',
            'address' => 'string',
        ], [], []);

        //convert tel number to integer
        try{
            $tel_number = intval($request->tel_number);
        }
        catch(\Exception $e)
        {
            return response()->json(['errors' => 'tel_number must be a number'], 422);
        }
        //get the user
        $user = $request->user();
        $user_id = $user->id;
        $user = $this->userRepository->findById($user_id);

        $user->tel_number = $request->tel_number;
        $user->address = $request->address;
        $user->save();

        return response()->json(['success' => ['user updated successfully'],
            'user' => $user]);
    }

    //revokeToken
    public function revokeToken(Request $request)
    {
        //validate the request
        $this->validate($request, [
            'token_id' => 'required|integer',
        ], [], []);

        $token_id = $request->token_id;
        $user = $request->user();
        $user->tokens()->where('id', $token_id)->delete();
        return response()->json(['success' => ['token revoked successfully']]);
    }

    //requestDeleteAccount
    public function requestDeleteAccount(Request $request)
    {
        $user = $request->user();
        $id = $user->id;
        $user = $this->userRepository->findById($id);
        if(!$user)
        {
            return response()->json(['message' => 'User does not exist'], 422);
        }
        //request the delete
        $user->request_delete_at = Carbon::now();
        $user->save();
        return response()->json(['success' => ['request sent successfully']]);
    }
}
