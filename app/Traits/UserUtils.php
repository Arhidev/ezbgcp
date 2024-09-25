<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Laravolt\Avatar\Avatar;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
trait UserUtils {

    public function deleteAccounts()
    {
        $auth = app('firebase.auth');
        $deleteDate = now()->subDays(3);
        //check the request to delete accounts
        $users = User::whereNotNull('request_delete_at')->where('request_delete_at', '<=', $deleteDate)->get();
        Log::info('Deleting accounts ' . $users->count());
        //remove the user's data from the database if the request_delete_at is 3 days ago
        foreach ($users as $user) {
            DB::beginTransaction();
            try {
                //get the user's uid
                $uid = $user->uid;
                //delete the user's data from Firebase
                $auth->deleteUser($uid);
                //delete the user's data from the database
                $user->delete();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
            }
        }
    }

    public function createToken($user, $name, $tokenAbility){
        $token = $user->createToken($name, $tokenAbility);
        return $token->plainTextToken;
    }

    //store avatar
    public function storeAvatar($user){
        $avatar = (new Avatar)->create($user->name)->getImageObject()->encode('png');
        //check if avatars/'.$user->id directory exists, if not create it
        if(!Storage::disk('public')->exists('avatars/'.$user->id)){
            Storage::disk('public')->makeDirectory('avatars/'.$user->id);
        }
        //store the image to storage/avatars/user-id/avatar.png
        $stored = Storage::disk('public')->put('avatars/'.$user->id.'/avatar.png', (string) $avatar);
        if($stored){
            $user->avatar = '/storage/avatars/'.$user->id.'/avatar.png';
            $user->save();
        }
    }

    //get Payment Method based on .env
    public function getPaymentMethod(){
        $merchantId = env('BRAINTREE_MERCHANT_ID');
        $api_key = env('RAZORPAY_KEY');
        $publicKey = env('FLW_PUBLIC_KEY');
        $paytabsProfileId = env('paytabs_profile_id');
        if($merchantId){
            $paymentMethod = 'braintree';
        } else if($api_key){
            $paymentMethod = 'razorpay';
        }
        else if($publicKey){
            $paymentMethod = 'flutterwave';
        }
        else if($paytabsProfileId){
            $paymentMethod = 'paytabs';
        }
        else
        {
            $paymentMethod = 'none';
        }
        return $paymentMethod;
    }
}
