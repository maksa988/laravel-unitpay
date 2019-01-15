<?php

namespace Maksa988\UnitPay\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string handle(Request $request)
 * @method static string getPayUrl($amount, $order_id, $email, $desc = null, $currency = null)
 * @method static string redirectToPayUrl($amount, $order_id, $email, $desc = null, $currency = null)
 * @method static string getFormSignature($account, $currency, $desc, $sum, $secretKey)
 *
 * @see \Maksa988\UnitPay\UnitPay
 */
class UnitPay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'unitpay';
    }
}