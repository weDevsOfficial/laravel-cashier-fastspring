<?php
/**
 * This file implements Billable.
 *
 * @author    Bilal Gultekin <bilal@gultekin.me>
 * @author    Justin Hartman <justin@22digital.co.za>
 * @copyright 2019 22 Digital
 * @license   MIT
 * @since     v0.1
 */

namespace TwentyTwoDigital\CashierFastspring;

use Exception;
use TwentyTwoDigital\CashierFastspring\Exceptions\NotImplementedException;
use TwentyTwoDigital\CashierFastspring\Fastspring\Fastspring;

/**
 * Billable trait.
 *
 * {@inheritdoc}
 */
trait Billable
{
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param int   $amount  The amount to charge
     * @param array $options Array of options
     *
     * @throws \TwentyTwoDigital\CashierFastspring\Exceptions\NotImplementedException
     */
    public function charge($amount, array $options = [])
    {
        throw new NotImplementedException();
    }

    /**
     * Refund a customer for a charge.
     *
     * @param string $charge  The amount to refund
     * @param array  $options Array of options
     *
     * @throws \TwentyTwoDigital\CashierFastspring\Exceptions\NotImplementedException
     */
    public function refund($charge, array $options = [])
    {
        throw new NotImplementedException();
    }

    /**
     * Begin creating a new subscription.
     *
     * @param string $subscription Subscription name
     * @param string $plan         The plan name
     *
     * @return \TwentyTwoDigital\CashierFastspring\SubscriptionBuilder
     */
    public function newSubscription($subscription, $plan)
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }

    /**
     * Determine if the subscription is on trial.
     *
     * @param string      $subscription Subscription name
     * @param string|null $plan         Plan name
     *
     * @return bool
     */
    public function onTrial($subscription = 'default', $plan = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($plan)) {
            return $subscription && $subscription->onTrial();
        }

        return $subscription && $subscription->onTrial() &&
               $subscription->plan === $plan;
    }

    /**
     * Determine if the model has a given subscription.
     *
     * @param string      $subscription Subscription name
     * @param string|null $plan         Plan name
     *
     * @return bool
     */
    public function subscribed($subscription = 'default', $plan = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($subscription)) {
            return false;
        }

        if (is_null($plan)) {
            return $subscription->valid();
        }

        return $subscription->valid() &&
               $subscription->plan === $plan;
    }

    /**
     * Get a subscription instance by name.
     *
     * @param string $subscription
     *
     * @return \TwentyTwoDigital\CashierFastspring\Subscription|null
     */
    public function subscription($subscription = 'default')
    {
        return $this->subscriptions()
            ->where('name', $subscription)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get all of the subscriptions for the model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Get all of the FastSpring invoices for the current user.
     *
     * @return object
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Determine if the model is actively subscribed to one of the given plans.
     *
     * @param string|null $plans        Plan name
     * @param string      $subscription Subscription name
     *
     * @return bool
     */
    public function subscribedToPlan($plans, $subscription = 'default')
    {
        $subscription = $this->subscription($subscription);

        if (!$subscription || !$subscription->valid()) {
            return false;
        }

        foreach ((array) $plans as $plan) {
            if ($subscription->plan === $plan) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the entity is on the given plan.
     *
     * @param string $plan Plan name
     *
     * @return bool
     */
    public function onPlan($plan)
    {
        return !is_null($this->subscriptions->first(function ($value) use ($plan) {
            return $value->plan === $plan && $value->valid();
        }));
    }

    /**
     * Determine if the entity has a Fastspring customer ID.
     *
     * @return bool
     */
    public function hasFastspringId()
    {
        return !is_null($this->fastspring_id);
    }

    /**
     * Generate authenticated url of fastspring account management panel.
     *
     * @return \TwentyTwoDigital\CashierFastspring\Fastspring\Fastspring
     */
    public function accountManagementURI()
    {
        $response = Fastspring::getAccountManagementURI($this->fastspring_id);

        return $response->accounts[0]->url;
    }

    /**
     * Create a Fastspring customer for the given user model.
     *
     * @param array $options Options array of customer information
     *
     * @return \TwentyTwoDigital\CashierFastspring\Fastspring\Fastspring
     */
    public function createAsFastspringCustomer(array $options = [])
    {
        $options = empty($options) ? [
            'contact' => [
                'first'   => $this->extractFirstName(),
                'last'    => $this->extractLastName(),
                'email'   => $this->email,
                'company' => $this->company,
                'phone'   => $this->phone,
            ],
            'language' => $this->language,
            'country'  => $this->country,
        ] : $options;

        // Here we will create the customer instance on Fastspring and store the ID of the
        // user from Fastspring. This ID will correspond with the Fastspring user instances
        // and allow us to retrieve users from Fastspring later when we need to work.
        $account = Fastspring::createAccount($options);

        $this->fastspring_id = $account->account;

        $this->save();

        return $account;
    }

    /**
     * Update the related account on Fastspring, given user-model.
     *
     * @param array $options array of customer information
     *
     * @throws Exception No valid Fastspring ID
     *
     * @return object
     */
    public function updateAsFastspringCustomer(array $options = [])
    {
        if (!$this->hasFastspringId()) {
            throw new Exception('User has no fastspring_id');
        }

        $options = empty($options) ? [
            'contact' => [
                'first'   => $this->extractFirstName(),
                'last'    => $this->extractLastName(),
                'email'   => $this->email,
                'company' => $this->company,
                'phone'   => $this->phone,
            ],
            'language' => $this->language,
            'country'  => $this->country,
        ] : $options;

        // update
        $response = Fastspring::updateAccount($this->fastspring_id, $options);

        return $response;
    }

    /**
     * Get the Fastspring customer for the model.
     *
     * @throws Exception No valid Fastspring ID
     *
     * @return object
     */
    public function asFastspringCustomer()
    {
        // check the fastspring_id first
        // if there is non, no need to try
        if (!$this->hasFastspringId()) {
            throw new Exception('User has no fastspring_id');
        }

        return Fastspring::getAccount($this->fastspring_id);
    }

    /**
     * Get the first name of the customer for the Fastspring API.
     *
     * @return object
     */
    public function extractFirstName()
    {
        $parted = explode(' ', $this->name);
        $parted = array_filter($parted);

        if (count($parted) == 1) {
            return $parted[0];
        }

        // get rid of the lastname
        array_pop($parted);

        // implode rest of it, so there may be more than one name
        return implode(' ', $parted);
    }

    /**
     * Get the last name of the customer for the Fastspring API.
     *
     * @return object
     */
    public function extractLastName()
    {
        $parted = explode(' ', $this->name);
        $parted = array_filter($parted);

        if (count($parted) == 1) {
            // unfortunately we should do this
            // because Fastspring create account API doesn't work without last name
            return 'Unknown';
        }

        // return last element
        return array_pop($parted);
    }
}
