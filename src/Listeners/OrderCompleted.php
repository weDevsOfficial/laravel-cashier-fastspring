<?php
/**
 * This class is a listener for order completed events.
 *
 * It updates or creates related order model so that you can show payment
 * and bill details to your customers.
 *
 * @author    Bilal Gultekin <bilal@gultekin.me>
 * @author    Justin Hartman <justin@22digital.co.za>
 * @copyright 2019 22 Digital
 * @license   MIT
 * @since     v0.1
 */

namespace TwentyTwoDigital\CashierFastspring\Listeners;

use TwentyTwoDigital\CashierFastspring\Events;
use TwentyTwoDigital\CashierFastspring\Invoice;

/**
 * This class describes an order completed.
 *
 * Note: "order.completed" event is works just at creation for subscription products.
 * IMPORTANT: This class handles expansion enabled webhooks
 *
 * {@inheritdoc}
 */
class OrderCompleted extends Base
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
     * @param \TwentyTwoDigital\CashierFastspring\Events\OrderCompleted $event
     *
     * @return void
     */
    public function handle(Events\OrderCompleted $event)
    {
        // Try to find that invoice on the database if not exists then create
        // one
        $data = $event->data;
        $subscription = $data['items'][0]['subscription'];

        $invoice = Invoice::firstOrNew([
            'fastspring_id' => $data['id'],
            'type'          => 'subscription',
        ]);

        $periodStartDate = $subscription['nextInSeconds'];
        $periodEndDate = $subscription['beginInSeconds'];

        // fill the model
        $invoice->subscription_sequence = $subscription['sequence'];
        $invoice->user_id = $this->getUserByFastspringId($data['account']['id'])->id;
        $invoice->subscription_display = $subscription['display'];
        $invoice->subscription_product = $subscription['product'];
        $invoice->invoice_url = $data['invoiceUrl'];
        $invoice->total = $data['total'];
        $invoice->tax = $data['tax'];
        $invoice->subtotal = $data['subtotal'];
        $invoice->discount = $data['discount'];
        $invoice->currency = $data['currency'];
        $invoice->payment_type = $data['payment']['type'];
        $invoice->completed = $data['completed'];
        $invoice->subscription_period_start_date = date('Y-m-d H:i:s', $periodStartDate);
        $invoice->subscription_period_end_date = date('Y-m-d H:i:s', $periodEndDate);

        // and save
        $invoice->save();
    }
}
