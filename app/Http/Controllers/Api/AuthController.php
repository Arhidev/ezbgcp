<?php

namespace App\Http\Controllers\Api;

use App\Models\Place;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Repository\UserRepositoryInterface;
use App\Repository\DriverInformationRepositoryInterface;
use App\Repository\DriverDocumentRepositoryInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use DB;

use Validator;
use App\Traits\UserUtils;
use App\Traits\AuthSec;

class AuthController extends Controller
{
    use AuthSec;
    use UserUtils;
    private $auth;
    private $userRepository;
    private $driverInformationRepository;
    private $driverDocumentRepository;
    public function __construct(
        Auth $auth,
        UserRepositoryInterface $userRepository,
        DriverInformationRepositoryInterface $driverInformationRepository,
        DriverDocumentRepositoryInterface $driverDocumentRepository
    ) {
        $this->userRepository = $userRepository;
        $this->auth = $auth;
        $this->driverInformationRepository = $driverInformationRepository;
        $this->driverDocumentRepository = $driverDocumentRepository;
    }

    //resetPassword
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            //pass validator errors as errors object for ajax response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        //send password reset email to user email if it exists
        $user = $this->userRepository->findByWhere([['email', '=', $request->email]])->first();

        try{
            //send password reset email to user email if it exists
            $token = $this->auth->sendPasswordResetLink($request->email);

            return response()->json(['message' => 'Reset password link sent on your email id.']);
        }
        catch(\Exception $e)
        {
            //user not found
            return response()->json($e->getMessage(), 422);
        }

    }

    public function loginViaToken(Request $request)
    {
        Log::info('loginViaToken');
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            //pass validator errors as errors object for ajax response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            //authenticate user using firebase token
            $verifiedIdToken = $this->auth->verifyIdToken($request->token);
        } catch (InvalidToken $e) {
            return response()->json(['errors' => ['authentication' => ['The token is invalid: ' . $e->getMessage()]]], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['errors' => ['authentication' => ['The token could not be parsed: ' . $e->getMessage()]]], 403);
        }

        //get user id
        $uid = $verifiedIdToken->claims()->get('sub');

        $user = $this->userRepository->findByWhere([['uid', '=', $uid]])->first();
        if ($user) {
            $user->request_delete_at = null;
            $user->save();
            //user exists
            if (($user->role == 1 && $user->status_id != 1) ||($user->role == 2 && $user->status_id == 3))  {
                //user is not active
                return response()->json(['errors' => ['authentication' => ['User is not active. Please contact the admin.']]], 403);
            }
            $user->tokens()->where('name', $request->device_name)->delete();
            if ($request->has('fcm_token')) {
                $user->fcm_token =  $request->fcm_token;
                $user->save();
            }
            //create token
            $tokenAbility = "";
            if ($user->role == 0)
                $tokenAbility = "admin";
            else if ($user->role == 1)
                $tokenAbility = "customer";
            else
                $tokenAbility = "driver";

            $token = $this->createToken($user, $request->device_name, [$tokenAbility]);
            if($user->role == 1 || $user->role == 2)
            {
                $token = $this->get_sec_id($token);
                if($token == null)
                {
                    return response()->json(['errors' => ['authentication' => ['Error in generating token']]], 403);
                }
            }
            $driverInformation = null;
            if($user->role == 2)
            {
                $user_id = $user->id;
                //get driver information
                $driverInformation = $this->driverInformationRepository->findByWhere(['user_id' => $user_id])->first();
                // if(!$driverInformation)
                // {
                //     return response()->json(['error' => ['Driver information does not exist']], 422);
                // }
                if($driverInformation)
                {
                    //get driver documents
                    $driverDocuments = $this->driverDocumentRepository->findByWhere(['driver_information_id' => $driverInformation->id]);
                    $driverInformation->documents = $driverDocuments;
                }

                //get user info
                $userInfo = $this->userRepository->findById($user_id);
            }
            //return token and user data
            return response()->json([
                'token' => $token,
                'user_data' => $user,
                'driver_data' => $driverInformation,
                'admin' => ($user->role == 0)
            ]);
        } else {

            //validate the request. Make sure it contains role
            $validator = Validator::make($request->all(), [
                'role' => 'required|integer',
            ]);

            if ($request->role == 0) {
                return response()->json(['errors' => ['authentication' => ['User does not exist']]], 403);
            }
            //create user
            $user = new User();
            $user->uid = $uid;
            $user->role = $request->role;
            $user->status_id = 1;
            $user->name = "";
            if ($request->has('fcm_token')) {
                $user->fcm_token =  $request->fcm_token;
            }
            $user->save();
            //create token
            $token = $this->createToken($user, $request->device_name, ["customer"]);
            //return token and user data
            return response()->json([
                'token' => $token,
                'user_data' => $user
            ]);
        }
    }
    public function login(LoginRequest $request)
    {
        try {
            if (Auth::attempt($request->only('email', 'password'))) {
                /** @var User $user */
                $user = Auth::user();
                $token = $user->createToken('API Token')->accessToken;

                if (config('auth.must_verify_email') && !$user->hasVerifiedEmail()) {
                    return response([
                        'message' => 'Email must be verified.'
                    ], 401);
                }

                return response([
                    'message' => 'success',
                    'token' => $token,
                    'user_data' => $user
                ]);
            }
        } catch (\Exception $e) {
            return response([
                'message' => 'Internal error, please try again later.' //$e->getMessage()
            ], 400);
        }

        return response([
            'message' => 'Invalid Email or password.'
        ], 401);
    }

    public function user()
    {
        //return the user object
        return response()->json(Auth::user());
    }

    public function createCustomer(Request $request)
    {
        return $this->createCustomerDriver($request, 1);
    }

    public function createDriver(Request $request)
    {
        return $this->createCustomerDriver($request, 2);
    }

    private function createCustomerDriver(Request $request, Int $role)
    {
        $uid = $this->getUserUid($request, $role);
        $user_exist = false;

        // check if user already exists and he is a customer
        $user = User::where('role', $role)->where('uid', $uid)->first();
        if($user)
        {
            $user_exist = true;
        }

        try{
            if($user_exist)
            {
                //user exists
                return $this->existedUserData($request, $user);
            }
            else{
                //user does not exist
                return $this->newUserData($uid, $request, $role);
            }
        }
        catch(UserNotFound $e)
        {
            //user not found
            return response()->json(['errors' => ['authentication' => ['User not found: '.$e->getMessage()]]], 403);
        }
    }

    private function getUserUid($request, $role)
    {
        $validators = [
            'token' => 'required|string',
            'name' => 'required|string',
            'device_name' => 'required',
        ];

        $validators = $role == 0 ? array_merge($validators, ['role' => 'required']) : $validators;

        $validator = Validator::make($request->all(), $validators);

        if ($validator->fails()) {
            //pass validator errors as errors object for ajax response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($request->token);
        } catch (InvalidToken $e) {
            Log::info('getUserUid error', ['error' => $e->getMessage()]);
            return response()->json(['errors' => ['authentication' => ['The token is invalid: '.$e->getMessage()]]], 403);
        } catch (\InvalidArgumentException $e) {
            Log::info('getUserUid error', ['error' => $e->getMessage()]);
            return response()->json(['errors' => ['authentication' => ['The token could not be parsed: '.$e->getMessage()]]], 403);
        }
        //get user id
        $uid = $verifiedIdToken->claims()->get('sub');
        return $uid;
    }
    private function newUserData($uid, $request, $role)
    {
        //create a transaction for all operations
        DB::beginTransaction();
        try {
            $authUser = $this->auth->getUser($uid);
            //log user
            Log::info('User logged in', ['user_data' => $authUser]);
            $localUser = User::create([
                'name' => $request->name,
                'email' => $authUser->email,
                'password' => $authUser->passwordHash!=null? $authUser->passwordHash : "",
                'uid' => $authUser->uid,
                'role' => $role,
                'status_id' => ($role==0 || $role==1) ? 1 : 2,
            ]);

            if($role == 1)
            {
                // $customer = \Stripe\Customer::create(['email' => $authUser->email]);
                // $localUser->stripe_id = $customer->id;
            }

            if($request->has('fcm_token'))
            {
                $localUser->fcm_token =  $request->fcm_token;
            }

            $this->storeAvatar($localUser);
            $localUser->save();

            if($role==0)
                $tokenAbility = "admin";
            else if($role==1)
                $tokenAbility = "customer";
            else
                $tokenAbility = "driver";

            $token = $this->createToken($localUser, $request->device_name, [$tokenAbility]);
            if($localUser->role == 1 || $localUser->role == 2)
            {
                $token = $this->get_sec_id($token);
                if($token == null)
                {
                    return response()->json(['errors' => ['authentication' => ['Error in generating token']]], 403);
                }
            }
            //add home and work places for customer
            if($role == 1)
            {
                //add home and work places
                $home = new Place();
                $home->user_id = $localUser->id;
                $home->name = "Home";
                $home->type = 1;
                $home->favorite = 1;
                $home->latitude = 0;
                $home->longitude = 0;
                $home->address = "";
                $home->save();

                $work = new Place();
                $work->user_id = $localUser->id;
                $work->name = "Work";
                $work->type = 2;
                $work->favorite = 1;
                $work->latitude = 0;
                $work->longitude = 0;
                $work->address = "";
                $work->save();
            }

            DB::commit();
            return response()->json(['token' => $token,
            'user_data' => $localUser, 'admin' => ($localUser->role==0),
            ]);
        } catch (\Exception $e) {
            // delete firebase user if any error
            $this->auth->deleteUser($uid);
            //rollback transaction
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
    private function existedUserData($request, $user)
    {
        //user exists
        if (($user->role == 1 && $user->status_id != 1) ||($user->role == 2 && $user->status_id == 3))
        {
            //user is not active
            return response()->json(['errors' => ['authentication' => ['User is not active. Please contact the admin.']]], 403);
        }
        //delete all tokens for this device
        $user->tokens()->where('name', $request->device_name)->delete();
        if($request->has('fcm_token'))
        {
            $user->fcm_token =  $request->fcm_token;
            $user->save();
        }
        //create token
        $tokenAbility = "";
        if($user->role==0)
            $tokenAbility = "admin";
        else if($user->role==1)
            $tokenAbility = "customer";
        else
            $tokenAbility = "driver";

        //create token
        $token = $this->createToken($user, $request->device_name, [$tokenAbility]);
        if($user->role == 1 || $user->role == 2)
        {
            $token = $this->get_sec_id($token);
            if($token == null)
            {
                return response()->json(['errors' => ['authentication' => ['Error in generating token']]], 403);
            }
        }
        return response()->json(['token' => $token, 'user_data' => $user, 'admin' => ($user->role == 0)]);
    }

    //verifyUser
    public function verifyUser(Request $request)
    {
        //get the auth user
        $user = $request->user();

        if (($user->role == 1 && $user->status_id != 1) ||($user->role == 2 && $user->status_id == 3))
        {
            return response()->json(['errors' => ['authentication' => ['User is not active. Please contact the admin.']]], 403);
        }

        return response()->json(['user_data' => $user]);
    }
}
