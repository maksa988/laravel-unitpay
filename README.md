# Laravel payment processor package for UnitPay gateway

[![Latest Stable Version](https://poser.pugx.org/maksa988/laravel-unitpay/v/stable)](https://packagist.org/packages/maksa988/laravel-unitpay)
[![Build Status](https://travis-ci.org/maksa988/laravel-unitpay.svg?branch=master)](https://travis-ci.org/maksa988/laravel-unitpay)
[![CodeFactor](https://www.codefactor.io/repository/github/maksa988/laravel-unitpay/badge)](https://www.codefactor.io/repository/github/maksa988/laravel-unitpay)
[![Quality Score](https://img.shields.io/scrutinizer/g/maksa988/laravel-unitpay.svg?style=flat-square)](https://scrutinizer-ci.com/g/maksa988/laravel-unitpay)
[![Total Downloads](https://img.shields.io/packagist/dt/maksa988/laravel-unitpay.svg?style=flat-square)](https://packagist.org/packages/maksa988/laravel-unitpay)
[![License](https://poser.pugx.org/maksa988/laravel-unitpay/license)](https://packagist.org/packages/maksa988/laravel-unitpay)

Accept payments via UnitPay ([unitpay.ru](https://unitpay.ru/)) using this Laravel framework package ([Laravel](https://laravel.com)).

- receive payments, adding just the two callbacks

#### Laravel 5.3, 5.4, PHP >= 5.6.4

## Installation

Require this package with composer.

``` bash
composer require maksa988/laravel-unitpay
```

If you don't use auto-discovery, add the ServiceProvider to the providers array in `config/app.php`

```php
Maksa988\UnitPay\UnitPayServiceProvider::class,
```

Add the `UnitPay` facade to your facades array:

```php
'UnitPay' => Maksa988\UnitPay\Facades\UnitPay::class,
```

Copy the package config to your local config with the publish command:
``` bash
php artisan vendor:publish --provider="Maksa988\UnitPay\UnitPayServiceProvider"
```

## Configuration

Once you have published the configuration files, please edit the config file in `config/unitpay.php`.

- Create an account on [unitpay.ru](http://unitpay.ru)
- Add your project, copy the `public_key`, `secret_key` params and paste into `config/unitpay.php`
- After the configuration has been published, edit `config/unitpay.php`
- Set the callback static function for `searchOrder` and `paidOrder`
- Create route to your controller, and call `UnitPay::handle` method
 
## Usage

1) Generate a payment url or get redirect:

```php
$amount = 100; // Payment`s amount

$email = "example@gmail.com"; // Your customer`s email

$description = "Test payment";

//

$url = UnitPay::getPayUrl($amount, $order_id, $email, $description, $currency);

$redirect = UnitPay::redirectToPayUrl($amount, $order_id, $email, $description, $currency);
```

2) Process the request from UnitPay:
``` php
UnitPay::handle(Request $request)
```

## Important

You must define callbacks in `config/unitpay.php` to search the order and save the paid order.


``` php
'searchOrder' => null  // UnitPayController@searchOrder(Request $request)
```

``` php
'paidOrder' => null  // UnitPayController@paidOrder(Request $request, $order)
```

## Example

The process scheme:

1. The request comes from `unitpay.ru` `GET` `http://yourproject.com/unitpay/result` (with params).
2. The function`UnitPayController@handlePayment` runs the validation process (auto-validation request params).
3. The method `searchOrder` will be called (see `config/unitpay.php` `searchOrder`) to search the order by the unique id.
4. If the current order status is NOT `paid` in your database, the method `paidOrder` will be called (see `config/unitpay.php` `paidOrder`).

Add the route to `routes/web.php`:
``` php
 Route::get('/unitpay/result', 'UnitPayController@handlePayment');
```

> **Note:**
don't forget to save your full route url (e.g. http://example.com/unitpay/result ) for your project on [unitpay.ru](unitpay.ru).

Create the following controller: `/app/Http/Controllers/UnitPayController.php`:

``` php
class UnitPayController extends Controller
{
    /**
     * Search the order in your database and return that order
     * to paidOrder, if status of your order is 'paid'
     *
     * @param Request $request
     * @param $order_id
     * @return bool|mixed
     */
    public function searchOrder(Request $request, $order_id)
    {
        $order = Order::where('id', $order_id)->first();

        if($order) {
            $order['_orderSum'] = $order->sum;

            // If your field can be `paid` you can set them like string
            $order['_orderStatus'] = $order['status'];

            // Else your field doesn` has value like 'paid', you can change this value
            $order['_orderStatus'] = ('1' == $order['status']) ? 'paid' : false;

            return $order;
        }

        return false;
    }

    /**
     * When paymnet is check, you can paid your order
     *
     * @param Request $request
     * @param $order
     * @return bool
     */
    public function paidOrder(Request $request, $order)
    {
        $order->status = 'paid';
        $order->save();

        //

        return true;
    }

    /**
     * Start handle process from route
     *
     * @param Request $request
     * @return mixed
     */
    public function handlePayment(Request $request)
    {
        return UnitPay::handle($request);
    }
}
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please send me an email at maksa988ua@gmail.com instead of using the issue tracker.

## Credits

- [Maksa988](https://github.com/maksa988)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.