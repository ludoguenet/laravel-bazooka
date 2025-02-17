<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka;

use Illuminate\Support\ServiceProvider;
use LaravelJutsu\Bazooka\Console\InjectChaosCommand;
use LaravelJutsu\Bazooka\Console\ListChaosPointsCommand;
use LaravelJutsu\Bazooka\Console\RemoveChaosPointsCommand;
use LaravelJutsu\Bazooka\Strategies\ExceptionDisruption;
use LaravelJutsu\Bazooka\Strategies\LatencyDisruption;

class BazookaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fusionner la configuration avec celle existante
        $this->mergeConfigFrom(
            __DIR__.'/config/bazooka.php', 'bazooka'
        );

        $this->app->singleton('bazooka', function ($app) {
            $config = $app['config']['bazooka'];
            $manager = new BazookaManager($config);

            // Ajouter des stratégies en fonction de la configuration
            if ($config['strategies']['latency']['enabled']) {
                $manager->addStrategy(new LatencyDisruption($config['strategies']['latency']));
            }

            if ($config['strategies']['exception']['enabled']) {
                $manager->addStrategy(new ExceptionDisruption($config['strategies']['exception']));
            }

            return $manager;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/config/bazooka.php' => config_path('bazooka.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InjectChaosCommand::class,
                ListChaosPointsCommand::class,
                RemoveChaosPointsCommand::class,
            ]);

            $this->optimizes(
                optimize: 'package:optimize',
                clear: 'package:clear-optimizations',
            );
        }
    }
}
