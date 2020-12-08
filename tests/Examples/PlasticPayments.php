<?php
/**
 * Contains the PlasticPayments class.
 *
 * @copyright   Copyright (c) 2019 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2019-12-26
 *
 */

namespace Vanilo\Payment\Tests\Examples;

use Vanilo\Contracts\Payable;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Requests\NullRequest;

class PlasticPayments implements PaymentGateway
{
    public static function getName(): string
    {
        return 'Plastic Payments';
    }

    public function createPaymentRequest(Payable $payable): PaymentRequest
    {
        return new NullRequest($payable);
    }

    public function isOffline(): bool
    {
        return false;
    }
}
