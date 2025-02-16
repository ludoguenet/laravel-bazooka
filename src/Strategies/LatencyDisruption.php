<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Strategies;

use LaravelJutsu\Bazooka\Contracts\DisruptionStrategy;

class LatencyDisruption implements DisruptionStrategy
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function execute(): void
    {
        $delay = random_int(
            $this->config['min_delay'],
            $this->config['max_delay']
        );

        usleep($delay * 1000);
    }
}
