<?php

namespace Bread\BreadCheckout\Model\Payment\Api;

/**
 * Interface ServiceInterface
 * @package Bread\Core\Model\Api
 */
interface ServiceInterface
{
    const STATUS_AUTHORIZED     = 'AUTHORIZED';
    const STATUS_SETTLED        = 'SETTLED';
    const STATUS_PENDING        = 'PENDING';
    const STATUS_CANCELED       = 'CANCELED';

    /**
     * Associative array containing short reference as key and value templates
     */
    const API_ACTIONS = [
        'cart_create'        => 'carts/',
        'info'               => 'transactions/%s',
        'update_transaction' => 'transactions/actions/%s',
        'update_shipping'    => 'transactions/%s/shipment',
        'send_sms'           => 'carts/%s/text',
        'send_email'         => 'carts/%s/email',
        'aslowas'            => 'aslowas'
    ];

}