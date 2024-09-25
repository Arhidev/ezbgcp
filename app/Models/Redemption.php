<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redemption extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function redemptionType()
    {
        return $this->belongsTo(RedemptionType::class, 'redemption_type_id');
    }

    //user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //BankAccount
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    //MobileMoney
    public function mobileMoneyAccount()
    {
        return $this->belongsTo(MobileMoneyAccount::class, 'mobile_money_account_id');
    }

    //Paypal
    public function paypalAccount()
    {
        return $this->belongsTo(PaypalAccount::class, 'paypal_account_id');
    }

}
