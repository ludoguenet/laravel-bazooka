<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka;

use LaravelJutsu\Bazooka\Contracts\DisruptionStrategy;

class BazookaManager
{
    private array $strategies = [];

    private bool $enabled;

    private float $probability;

    public function __construct(array $config)
    {
        $this->enabled = $config['enabled'] ?? false;
        $this->probability = $config['probability'] ?? 0.2;
    }

    public function chaos(): void
    {
        if (! $this->enabled || ! $this->shouldDisrupt()) {
            return;
        }

        $strategy = $this->getRandomStrategy();
        if ($strategy) {
            $strategy->execute();
        }
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function addStrategy(DisruptionStrategy $strategy): self
    {
        $this->strategies[] = $strategy;

        return $this;
    }

    private function shouldDisrupt(): bool
    {
        return (mt_rand() / mt_getrandmax()) < $this->probability;
    }

    private function getRandomStrategy(): ?DisruptionStrategy
    {
        if (empty($this->strategies)) {
            return null;
        }

        return $this->strategies[array_rand($this->strategies)];
    }
}
