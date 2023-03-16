<?php

namespace Takeaway\Models;

use Takeaway\Model;

/**
 * A choice belonging to a product.
 *
 * @property string $id Unique identifier of the choice.
 * @property string $name Name of the choice.
 * @property float $deliveryPrice Delivery price of the choice.
 * @property float $pickupPrice Pickup price of the choice.
 * @property string|array $allergens
 * @property string|array $additives
 * @property boolean $excludedFromMinimum Whether or not the choice is excluded
 *                                        from the minum order amount.
 */
class Choice extends Model
{
}
