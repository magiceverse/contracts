<?php

namespace Magiceverse\Contracts\Envelope;

/**
 * CloudEvents `type` of the product webhooks.
 */
enum EventType: string
{
    case ProductUpdated = 'dev.magiceverse.product.updated';
    case ProductDeleted = 'dev.magiceverse.product.deleted';
}
