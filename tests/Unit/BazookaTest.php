<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use LaravelJutsu\Bazooka\BazookaManager;
use LaravelJutsu\Bazooka\Console\InjectChaosCommand;
use LaravelJutsu\Bazooka\Contracts\DisruptionStrategy;
use LaravelJutsu\Bazooka\Strategies\ExceptionDisruption;
use LaravelJutsu\Bazooka\Strategies\LatencyDisruption;

beforeEach(function () {
    File::ensureDirectoryExists(app_path('Http/Controllers'));
});

afterEach(function () {
    File::deleteDirectory(app_path('Http/Controllers'));
});

/*
|--------------------------------------------------------------------------
| BazookaManager Tests
|--------------------------------------------------------------------------
*/

test('manager can be enabled and disabled', function () {
    $manager = new BazookaManager(['enabled' => true]);

    expect($manager)->toBeInstanceOf(BazookaManager::class);

    $manager->disable();

    $executed = false;
    $strategy = new class implements DisruptionStrategy
    {
        public function execute(): void
        {
            $GLOBALS['executed'] = true;
        }
    };

    $manager->addStrategy($strategy);
    $manager->chaos();

    expect($GLOBALS['executed'] ?? false)->toBeFalse();
});

test('manager executes strategy based on probability', function () {
    $manager = new BazookaManager([
        'enabled' => true,
        'probability' => 1.0,
    ]);

    $executed = false;
    $strategy = new class implements DisruptionStrategy
    {
        public function execute(): void
        {
            $GLOBALS['executed'] = true;
        }
    };

    $manager->addStrategy($strategy);
    $manager->chaos();

    expect($GLOBALS['executed'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Strategy Tests
|--------------------------------------------------------------------------
*/

test('latency disruption adds delay', function () {
    $config = [
        'min_delay' => 100,
        'max_delay' => 200,
    ];

    $strategy = new LatencyDisruption($config);

    $startTime = microtime(true);
    $strategy->execute();
    $endTime = microtime(true);

    $delayMs = ($endTime - $startTime) * 1000;
    expect($delayMs)->toBeGreaterThan(100)->toBeLessThan(200);
});

test('exception disruption throws configured exception', function () {
    $config = [
        'exceptions' => [
            \RuntimeException::class => 'Test Exception',
        ],
    ];

    $strategy = new ExceptionDisruption($config);

    expect(fn () => $strategy->execute())
        ->toThrow(\RuntimeException::class, 'Test Exception');
});

/*
|--------------------------------------------------------------------------
| Command Tests
|--------------------------------------------------------------------------
*/

test('inject command respects enabled configuration', function () {
    Config::set('bazooka.enabled', false);

    $this->artisan(InjectChaosCommand::class)
        ->expectsOutput('Bazooka is currently disabled in configuration.')
        ->assertExitCode(1);
});

test('inject command handles non-existent controllers', function () {
    Config::set('bazooka.enabled', true);

    $controllerContent = <<<'PHP'
    <?php

    namespace App\Http\Controllers;

    class ExistingController
    {
        public function index()
        {
            return 'Hello World';
        }
    }
    PHP;

    File::put(app_path('Http/Controllers/ExistingController.php'), $controllerContent);

    $this->artisan(InjectChaosCommand::class, ['--controller' => ['NonExistentController']])
        ->expectsOutput('Warning: Controller not found: NonExistentController')
        ->expectsOutput('No controllers found to process.')
        ->assertExitCode(1);
});

test('inject command prevents duplicate chaos calls', function () {
    Config::set('bazooka.enabled', true);
    Config::set('bazooka.probability', 1);

    $controllerContent = <<<'PHP'
    <?php

    namespace App\Http\Controllers;

    class TestController
    {
        public function index()
        {
            \LaravelJutsu\Bazooka\Facades\Bazooka::chaos();
            return 'Hello World';
        }
    }
    PHP;

    File::put(app_path('Http/Controllers/TestController.php'), $controllerContent);

    $this->artisan(InjectChaosCommand::class)
        ->expectsOutput('Processed 1 controller.')
        ->expectsOutput('Injected chaos into 0 methods.')
        ->assertExitCode(0);
});

test('inject command injects chaos into controllers', function () {
    Config::set('bazooka.enabled', true);
    Config::set('bazooka.probability', 1);

    $controllerContent = <<<'PHP'
    <?php

    namespace App\Http\Controllers;

    class TestController
    {
        public function index()
        {
            return 'Hello World';
        }
    }
    PHP;

    File::put(app_path('Http/Controllers/TestController.php'), $controllerContent);

    $this->artisan(InjectChaosCommand::class)
        ->expectsOutput('Processed 1 controller.')
        ->expectsOutput('Injected chaos into 1 method.')
        ->assertExitCode(0);

    $modifiedContent = File::get(app_path('Http/Controllers/TestController.php'));
    expect($modifiedContent)->toContain('\LaravelJutsu\Bazooka\Facades\Bazooka::chaos()');
});

/*
|--------------------------------------------------------------------------
| Edge Cases and Error Handling
|--------------------------------------------------------------------------
*/

test('inject command handles invalid PHP files gracefully', function () {
    Config::set('bazooka.enabled', true);

    $invalidContent = <<<'PHP'
    <?php

    namespace App\Http\Controllers;

    class InvalidController
    {
        public function index()
        {
            This is invalid PHP code!
        }
    }
    PHP;

    File::put(app_path('Http/Controllers/InvalidController.php'), $invalidContent);

    $this->artisan(InjectChaosCommand::class)
        ->expectsOutputToContain('Error processing InvalidController.php: Syntax error, unexpected T_STRING')
        ->assertExitCode(0);
});

test('inject command handles empty controller directory', function () {
    Config::set('bazooka.enabled', true);

    File::deleteDirectory(app_path('Http/Controllers'));

    $this->artisan(InjectChaosCommand::class)
        ->expectsOutput('No controllers found to process.')
        ->assertExitCode(1);
});

/*
|--------------------------------------------------------------------------
| Remove Command Tests
|--------------------------------------------------------------------------
*/

test('remove command removes chaos points', function () {
    Config::set('bazooka.enabled', true);

    $controllerContent = <<<'PHP'
    <?php

    namespace App\Http\Controllers;

    class TestController
    {
        public function index()
        {
            \LaravelJutsu\Bazooka\Facades\Bazooka::chaos();
            return 'Hello World';
        }
    }
    PHP;

    File::put(app_path('Http/Controllers/TestController.php'), $controllerContent);

    $this->artisan('bazooka:remove')
        ->expectsOutput('Removed 1 chaos points from Http/Controllers/TestController.php')
        ->expectsOutput('Removed 1 chaos points in total.')
        ->assertExitCode(0);

    $modifiedContent = File::get(app_path('Http/Controllers/TestController.php'));
    expect($modifiedContent)->not->toContain('\LaravelJutsu\Bazooka\Facades\Bazooka::chaos()');
});

test('remove command has dry-run option', function () {
    Config::set('bazooka.enabled', true);

    $controllerContent = <<<'PHP'
    <?php

    namespace App\Http\Controllers;

    class TestController
    {
        public function index()
        {
            \LaravelJutsu\Bazooka\Facades\Bazooka::chaos();
            return 'Hello World';
        }
    }
    PHP;

    File::put(app_path('Http/Controllers/TestController.php'), $controllerContent);

    $this->artisan('bazooka:remove', ['--dry-run' => true])
        ->expectsOutput('Would remove 1 chaos points from Http/Controllers/TestController.php')
        ->expectsOutput('Would remove 1 chaos points in total.')
        ->assertExitCode(0);

    $modifiedContent = File::get(app_path('Http/Controllers/TestController.php'));
    expect($modifiedContent)->toContain('\LaravelJutsu\Bazooka\Facades\Bazooka::chaos();');
});
