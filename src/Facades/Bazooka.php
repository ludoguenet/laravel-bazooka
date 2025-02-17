<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Facades;

use Illuminate\Support\Facades\Facade;

class Bazooka extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bazooka';
    }
}
