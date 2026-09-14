<?php

namespace App\Enums;

enum OrderStatus: string
{
    /** Checkout started, the gateway has not confirmed anything yet. */
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
