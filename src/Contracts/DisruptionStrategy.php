<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Contracts;

interface DisruptionStrategy
{
    public function execute(): void;
}
