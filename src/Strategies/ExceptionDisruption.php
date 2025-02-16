<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Strategies;

use LaravelJutsu\Bazooka\Contracts\DisruptionStrategy;

class ExceptionDisruption implements DisruptionStrategy
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function execute(): void
    {
        $exceptions = $this->config['exceptions'];
        $exceptionClass = array_rand($exceptions);
        $message = $exceptions[$exceptionClass];

        throw new $exceptionClass($message);
    }
}
