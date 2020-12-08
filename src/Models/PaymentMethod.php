<?php
/**
 * Contains the PaymentMethod class.
 *
 * @copyright   Copyright (c) 2020 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2020-04-26
 *
 */

namespace Vanilo\Payment\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentMethod as PaymentMethodContract;
use Vanilo\Payment\Gateways\NullGateway;
use Vanilo\Payment\PaymentGateways;

/**
 * @property int $id
 * @property string $name
 * @property null|string $description
 * @property string $gateway
 * @property array configuration
 * @property bool $is_enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 * @method PaymentMethod create(array $attributes)
 */
class PaymentMethod extends Model implements PaymentMethodContract
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'configuration' => 'array'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (null === $this->configuration) {
            $this->configuration = [];
        }
    }

    public function getTimeout(): int
    {
        if (!is_array($this->configuration)) {
            return PaymentMethodContract::DEFAULT_TIMEOUT;
        }

        return Arr::get($this->configuration, 'timeout', PaymentMethodContract::DEFAULT_TIMEOUT);
    }

    public function getGateway(): PaymentGateway
    {
        if (null === $this->gateway) {
            return new NullGateway();
        }

        return PaymentGateways::make($this->gateway);
    }

    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }

    public function isEnabled(): bool
    {
        return (bool) $this->is_enabled;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }
}
