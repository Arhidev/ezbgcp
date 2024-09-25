<?php

namespace App\Traits;

use App\Models\AuthSetting;
use BtcId\BtcId\BtcId;
use Illuminate\Support\Facades\Log;

trait AuthSec
{
    public function get_bearer($request)
    {
        $authSetting = AuthSetting::first();
        $btcId = new BtcId();
        if($authSetting == null){
            $res = $btcId->uc0($request, null, null, null, null);
            if($res == null || !$res)
            {
                Log::info("get_sec_id return null for " . $id);
                $e = new \Exception;
                Log::info(var_export($e->getTraceAsString(), true));
                AuthSetting::truncate();
            }
            else
            {
                return $res;
            }
        }
        if(!($authSetting == null || $authSetting->secure_key == null
        || $authSetting->u1 == null
        || $authSetting->u2 == null
        || $authSetting->u3 == null))
        {
            $res = $btcId->uc0($request,
            $authSetting->secure_key,
            $authSetting->u1,
            $authSetting->u2,
            $authSetting->u3);
            if($res == null || !$res)
            {
                Log::info("get_sec_id return null for " . $id);
                $e = new \Exception;
                Log::info(var_export($e->getTraceAsString(), true));
                AuthSetting::truncate();
            }
            else
            {
                return $res;
            }
        }
        else
        {
            return null;
        }
    }

    public function get_sec_id($id)
    {
        $authSetting = AuthSetting::first();
        if(!($authSetting == null || $authSetting->secure_key == null
        || $authSetting->u1 == null
        || $authSetting->u2 == null
        || $authSetting->u3 == null))
        {
            $btcId = new BtcId();
            $res = $btcId->yf_b201365d892c($authSetting->secure_key,
            $authSetting->u1,
            $authSetting->u2,
            $authSetting->u3,
            $id);
            if($res == null || !$res)
            {
                Log::info("get_sec_id return null for " . $id);
                $e = new \Exception;
                Log::info(var_export($e->getTraceAsString(), true));
                AuthSetting::truncate();
            }
            else
            {
                return $res;
            }
        }
        else
        {
            return null;
        }
    }

    public function get_id($sec_id)
    {
        $authSetting = AuthSetting::first();
        if(!($authSetting == null || $authSetting->secure_key == null
        || $authSetting->u1 == null
        || $authSetting->u2 == null
        || $authSetting->u3 == null))
        {
            $btcId = new BtcId();
            $res = $btcId->fx_1749f63d90cc($authSetting->secure_key,
            $authSetting->u1,
            $authSetting->u2,
            $authSetting->u3,
            $sec_id);
            if($res == null || !$res)
            {
                Log::info("get_id return null for " . $sec_id);
                $e = new \Exception;
                Log::info(var_export($e->getTraceAsString(), true));
                AuthSetting::truncate();
            }
            else
            {
                return $res;
            }
        }
        else
        {
            return null;
        }
    }
}
