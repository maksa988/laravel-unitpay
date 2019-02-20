<?php

namespace Maksa988\UnitPay\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ValidateTrait
{
    /**
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|in:check,pay,error',
            'params.account' => 'required',
            'params.date' => 'required',
            'params.payerSum' => 'required',
            'params.signature' => 'required',
            'params.orderSum' => 'required',
            'params.orderCurrency' => 'required',
            'params.unitpayId' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function validateSignature(Request $request)
    {
        $sign = $this->getSignature($request->get('method'), $request->get('params'), config('unitpay.secret_key'));

        if ($request->input('params.signature') != $sign) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function validateOrderFromHandle(Request $request)
    {
        return $this->AllowIP($request->ip())
                    && $this->validate($request)
                    && $this->validateSignature($request);
    }
}
