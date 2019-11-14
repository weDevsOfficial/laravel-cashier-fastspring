<?php

namespace TwentyTwoDigital\CashierFastspring\Listeners;

class Base
{
    /**
     * Get the billable entity instance by Fastspring ID.
     *
     * @param string $fastspringId
     *
     * @return \TwentyTwoDigital\CashierFastspring\Billable
     */
    public function getUserByFastspringId($fastspringId)
    {
        $model = getenv('FASTSPRING_MODEL') ?: config('services.fastspring.model');

        return (new $model())->where('fastspring_id', $fastspringId)->first();
    }
}
