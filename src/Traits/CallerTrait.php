<?php

namespace Maksa988\UnitPay\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Maksa988\UnitPay\Exceptions\InvalidPaidOrder;
use Maksa988\UnitPay\Exceptions\InvalidSearchOrder;

trait CallerTrait
{
    /**
     * @param Request $request
     * @return mixed
     *
     * @throws InvalidSearchOrder
     */
    public function callSearchOrder(Request $request)
    {
        if (is_null(config('unitpay.searchOrder'))) {
            throw new InvalidSearchOrder();
        }

        return App::call(config('unitpay.searchOrder'), ['order_id' => $request->input('params.account')]);
    }

    /**
     * @param Request $request
     * @param $order
     * @return mixed
     * @throws InvalidPaidOrder
     */
    public function callPaidOrder(Request $request, $order)
    {
        if (is_null(config('unitpay.paidOrder'))) {
            throw new InvalidPaidOrder();
        }

        return App::call(config('unitpay.paidOrder'), ['order' => $order]);
    }
}
