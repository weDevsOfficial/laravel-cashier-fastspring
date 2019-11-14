<?php

namespace TwentyTwoDigital\CashierFastspring\Tests\Fixtures;

use TwentyTwoDigital\CashierFastspring\Billable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class User extends Eloquent
{
    use Billable;
}
