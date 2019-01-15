<?php

namespace Maksa988\UnitPay\Exceptions;

use Throwable;

class InvalidPaidOrder extends \Exception
{
    /**
     * InvalidPaidOrder constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        if(empty($message))
            $message = "UnitPay config: paidOrder callback not set";

        parent::__construct($message, $code, $previous);
    }
}