<?php

namespace Maksa988\UnitPay\Test;

use Illuminate\Http\Request;
use Maksa988\UnitPay\Exceptions\InvalidPaidOrder;
use Maksa988\UnitPay\Exceptions\InvalidSearchOrder;
use Maksa988\UnitPay\Test\Fake\Order;

class UnitPayTest extends TestCase
{
    /** @test */
    public function test_env()
    {
        $this->assertEquals('testing', $this->app['env']);
    }

    /**
     * Create test request with custom method and add signature.
     * @param string $method
     * @param bool $signature
     * @return Request
     */
    protected function create_test_request($method = '', $signature = false)
    {
        $params = [
            'method' => $method,
            'params' => [
                'account' => '1',
                'date' => '1',
                'payerSum' => '1',
                'payerCurrency' => '1',
                'orderSum' => '1',
                'orderCurrency' => '1',
                'unitpayId' => '1',
            ],
        ];

        if ($signature === false) {
            $params['params']['signature'] = $this->unitpay->getSignature($method, $params['params'], $this->app['config']->get('unitpay.secret_key'));
        } else {
            $params['params']['signature'] = $signature;
        }

        $request = new Request($params);

        return $request;
    }

    /** @test */
    public function check_if_allow_remote_ip()
    {
        $this->assertTrue(
            $this->unitpay->allowIP('127.0.0.1')
        );

        $this->assertFalse(
            $this->unitpay->allowIP('0.0.0.0')
        );
    }

    /** @test */
    public function compare_form_signature()
    {
        $this->assertEquals(
            'da5ef2f0c2f69e99414cabdba2de9b1f8a5e233bcb91f600d6581d8b6460cca5',
            $this->unitpay->getFormSignature('account', 'RUB', 'desc', 'sum', 'secretkey')
        );
    }

    /** @test */
    public function generate_pay_url()
    {
        $url = $this->unitpay->getPayUrl(100, 10, 'example@gmail.com');

        $this->assertStringStartsWith($this->app['config']->get('unitpay.pay_url'), $url);
    }

    /** @test */
    public function compare_request_signature()
    {
        $params['account'] = '2222222e-2222-3333-3333-cd51f1605a02';
        $params['date'] = '2020-02-02 22:21:16';
        $params['ip'] = '123.123.123.123';
        $params['operator'] = 'yandex';
        $params['orderCurrency'] = 'RUB';
        $params['orderSum'] = '777.00';
        $params['payerCurrency'] = 'RUB';
        $params['payerSum'] = '777.00';
        $params['paymentType'] = 'yandex';
        $params['profit'] = '777.00';
        $params['projectId'] = '12345';
        $params['sum'] = '499';
        $params['test'] = '0';
        $params['unitpayId'] = '12345';

        $this->assertEquals(
            'a1e5f350f3c18386c0780a1ad7ae71b7b9beb0d657cc3544c33c6adda744312b',
            $this->unitpay->getSignature('pay', $params, 'secretkey')
        );
    }

    /** @test */
    public function pay_order_form_validate_request()
    {
        $request = $this->create_test_request('check');
        $this->assertTrue($this->unitpay->validate($request));

        $request = $this->create_test_request('pay');
        $this->assertTrue($this->unitpay->validate($request));

        $request = $this->create_test_request('error');
        $this->assertTrue($this->unitpay->validate($request));

        $request = $this->create_test_request('unknown');
        $this->assertFalse($this->unitpay->validate($request));
    }

    /** @test */
    public function validate_signature()
    {
        $request = $this->create_test_request('check', '3c34ad7ce9bb9fc56e8621e0a7797f3377136f365bcac07c4222575802d02b6d');
        $this->assertTrue($this->unitpay->validate($request));
        $this->assertTrue($this->unitpay->validateSignature($request));

        $request = $this->create_test_request('check', 'invalid_signature');
        $this->assertTrue($this->unitpay->validate($request));
        $this->assertFalse($this->unitpay->validateSignature($request));
    }

    /** @test */
    public function test_order_need_callbacks()
    {
        $request = $this->create_test_request('check', 'ec61edc55b99b7b62d8157dffd88895d72250e02163b1a60cd5f52d48d8a7015');
        $this->expectException(InvalidSearchOrder::class);
        $this->unitpay->callSearchOrder($request);

        $request = $this->create_test_request('check', 'ec61edc55b99b7b62d8157dffd88895d72250e02163b1a60cd5f52d48d8a7015');
        $this->expectException(InvalidPaidOrder::class);
        $this->unitpay->callPaidOrder($request, ['order_id' => '12345']);
    }

    /** @test */
    public function search_order_has_callbacks_fails()
    {
        $this->app['config']->set('unitpay.searchOrder', [Order::class, 'SearchOrderFilterFails']);
        $request = $this->create_test_request('check', 'ec61edc55b99b7b62d8157dffd88895d72250e02163b1a60cd5f52d48d8a7015');
        $this->assertFalse($this->unitpay->callSearchOrder($request));
    }

    /** @test */
    public function paid_order_has_callbacks()
    {
        $this->app['config']->set('unitpay.searchOrder', [Order::class, 'SearchOrderFilterPaid']);
        $this->app['config']->set('unitpay.paidOrder', [Order::class, 'PaidOrderFilter']);
        $request = $this->create_test_request('check', 'ec61edc55b99b7b62d8157dffd88895d72250e02163b1a60cd5f52d48d8a7015');
        $this->assertTrue($this->unitpay->callPaidOrder($request, ['order_id' => '12345']));
    }

    /** @test */
    public function paid_order_has_callbacks_fails()
    {
        $this->app['config']->set('unitpay.paidOrder', [Order::class, 'PaidOrderFilterFails']);
        $request = $this->create_test_request('check', 'ec61edc55b99b7b62d8157dffd88895d72250e02163b1a60cd5f52d48d8a7015');
        $this->assertFalse($this->unitpay->callPaidOrder($request, ['order_id' => '12345']));
    }

    /** @test */
    public function payOrderFromGate_SearchOrderFilter_fails()
    {
        $this->app['config']->set('unitpay.searchOrder', [Order::class, 'SearchOrderFilterFails']);
        $request = $this->create_test_request('check', 'ec61edc55b99b7b62d8157dffd88895d72250e02163b1a60cd5f52d48d8a7015');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->assertArrayHasKey('error', $this->unitpay->handle($request));
    }

    /** @test */
    public function payOrderFromGate_method_check_SearchOrderFilterPaid()
    {
        $this->app['config']->set('unitpay.searchOrder', [Order::class, 'SearchOrderFilterPaidforPayOrderFromGate']);
        $request = $this->create_test_request('check');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->assertArrayHasKey('result', $this->unitpay->handle($request));
    }

    /** @test */
    public function payOrderFromGate_method_pay_SearchOrderFilterPaid()
    {
        $this->app['config']->set('unitpay.searchOrder', [Order::class, 'SearchOrderFilterPaidforPayOrderFromGate']);
        $this->app['config']->set('unitpay.paidOrder', [Order::class, 'PaidOrderFilter']);
        $request = $this->create_test_request('pay');

        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->assertArrayHasKey('result', $this->unitpay->handle($request));
    }
}