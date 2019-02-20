<?php

namespace Maksa988\UnitPay;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Maksa988\UnitPay\Traits\CallerTrait;
use Maksa988\UnitPay\Traits\ValidateTrait;

class UnitPay
{
    use ValidateTrait;
    use CallerTrait;

    //

    /**
     * UnitPay constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $amount
     * @param $order_id
     * @param null $email
     * @param null $desc
     * @param null $currency
     * @return string
     */
    public function getPayUrl($amount, $order_id, $email, $desc = null, $currency = null)
    {
        // Array of url query
        $query = [];

        // Public key
        $url = rtrim(config('unitpay.pay_url'), '/').'/'.config('unitpay.public_key');

        // Amount of payment
        $query['sum'] = $amount;

        // Order id
        $query['account'] = $order_id;

        // User email
        $query['customerEmail'] = $email;

        // Locale for payment form
        $query['locale'] = config('unitpay.locale', 'ru');

        // Payment description
        if (! is_null($desc)) {
            $query['desc'] = $desc;
        }

        // Payment currency
        $query['currency'] = is_null($currency) ? config('unitpay.currency') : $currency;

        // Generate signature
        $query['signature'] = $this->getFormSignature($order_id, $query['currency'], $desc, $amount, config('unitpay.secret_key'));

        // Merge url ang query and return
        return $url.'?'.http_build_query($query);
    }

    /**
     * @param $amount
     * @param $order_id
     * @param null $email
     * @param null $desc
     * @param null $currency
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToPayUrl($amount, $order_id, $email, $desc = null, $currency = null)
    {
        return redirect()->away($this->getPayUrl($amount, $order_id, $email, $desc, $currency));
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function allowIP($ip)
    {
        // Allow local ip
        if ($ip == '127.0.0.1') {
            return true;
        }

        return in_array($ip, config('unitpay.allowed_ips'));
    }

    /**
     * @param $account
     * @param $currency
     * @param $desc
     * @param $sum
     * @param $secretKey
     * @return string
     */
    public function getFormSignature($account, $currency, $desc, $sum, $secretKey)
    {
        $hashStr = $account.'{up}'.$currency.'{up}'.$desc.'{up}'.$sum.'{up}'.$secretKey;

        return hash('sha256', $hashStr);
    }

    /**
     * @param $method
     * @param array $params
     * @param $secretKey
     * @return string
     */
    public function getSignature($method, array $params, $secretKey)
    {
        ksort($params);
        unset($params['sign'], $params['signature']);
        array_push($params, $secretKey);
        array_unshift($params, $method);

        return hash('sha256', implode('{up}', $params));
    }

    /**
     * @param Request $request
     * @return string
     * @throws Exceptions\InvalidPaidOrder
     * @throws Exceptions\InvalidSearchOrder
     */
    public function handle(Request $request)
    {
        // Validate request from UnitPay
        if (! $this->validateOrderFromHandle($request)) {
            return $this->responseError('validateOrderFromHandle');
        }

        // Search and get order
        $order = $this->callSearchOrder($request);

        if (! $order) {
            return $this->responseError('searchOrder');
        }

        // Return success response for check and error methods
        if (in_array($request->get('method'), ['check', 'error'])) {
            return $this->responseOK('OK');
        }

        // If method unknown then return error
        if ($request->get('method') != 'pay') {
            return $this->responseError('invalidRequest');
        }

        // If order already paid return success
        if (Str::lower($order['_orderStatus']) === 'paid') {
            return $this->responseOK('OK');
        }

        // PaidOrder - update order info
        // if return false then return error
        if (! $this->callPaidOrder($request, $order)) {
            return $this->responseError('paidOrder');
        }

        // Order is paid and updated, return success
        return $this->responseOK('OK');
    }

    /**
     * @param $error
     * @return string
     */
    public function responseError($error)
    {
        $result['error']['message'] = config('unitpay.errors.'.$error, $error);

        return $result;
    }

    /**
     * @param $message
     * @return string
     */
    public function responseOK($message)
    {
        $result['result']['message'] = $message;

        return $result;
    }
}
