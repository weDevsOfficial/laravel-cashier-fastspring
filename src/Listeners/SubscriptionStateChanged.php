<?php
/**
 * This file implements Subscription State Changed.
 *
 * @author    Bilal Gultekin <bilal@gultekin.me>
 * @author    Justin Hartman <justin@22digital.co.za>
 * @copyright 2019 22 Digital
 * @license   MIT
 * @since     v0.1
 */
namespace TwentyTwoDigital\CashierFastspring\Listeners;

use TwentyTwoDigital\CashierFastspring\Events;
use TwentyTwoDigital\CashierFastspring\Subscription;

/**
 * This class is a listener for subscription state change events.
 * It is planned to listen following fastspring events:
 *  - subscription.canceled
 *  - subscription.payment.overdue
 * It updates related subscription event.
 *
 * IMPORTANT: This class handles expansion enabled webhooks.
 *
 * {@inheritDoc}
 */
class SubscriptionStateChanged extends Base
{
    /**
     * Create the event listener.
     *
     * @return null
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param \TwentyTwoDigital\CashierFastspring\Events\Base $event
     *
     * @return void
     */
    public function handle(Events\Base $event)
    {
        $data = $event->data;

        // create
        $subscription = Subscription::where('fastspring_id', $data['id'])->firstOrFail();

        // fill
        $subscription->user_id = $this->getUserByFastspringId($data['account']['id'])->id;
        $subscription->plan = $data['product']['product'];
        $subscription->state = $data['state'];
        $subscription->currency = $data['currency'];
        $subscription->quantity = $data['quantity'];

        // save
        $subscription->save();
    }
}
