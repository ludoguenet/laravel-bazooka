<?php

declare(strict_types=1);

namespace Tests;

use LaravelJutsu\Bazooka\BazookaServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

abstract class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            BazookaServiceProvider::class,
        ];
    }
}
