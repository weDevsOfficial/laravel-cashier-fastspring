<?php

namespace TwentyTwoDigital\CashierFastspring\Tests;

use TwentyTwoDigital\CashierFastspring\Exceptions\NotImplementedException;
use Orchestra\Testbench\TestCase;

class ExceptionsTest extends TestCase
{
    public function testNotImplementedExceptionCanBeConstructed()
    {
        $this->assertInstanceOf(NotImplementedException::class, new NotImplementedException());
    }
}
