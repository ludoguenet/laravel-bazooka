# Laravel Bazooka 🔫

**Inject chaos into your Laravel application with ease!**

Laravel Jutsu Bazooka is a powerful Laravel package that allows you to inject controlled chaos into your application. Whether you're testing resilience, simulating failures, or just having fun, Bazooka provides a simple and configurable way to disrupt your application's behavior.

---

## Features

- **Inject Chaos Points**: Add `Bazooka::chaos()` calls to your controller methods with a configurable probability.
- **Disruption Strategies**: Choose from built-in strategies like latency delays or exception throwing.
- **Command-Line Tools**:
  - Inject chaos points into controllers.
  - List all chaos points in your application.
  - Remove chaos points from your codebase.
- **Dry Run Mode**: Safely test the removal of chaos points without modifying files.
- **Configurable Probability**: Control the likelihood of chaos being injected or triggered.

---

## Installation

You can install the package via Composer:

```bash
composer require laravel-jutsu/bazooka
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="LaravelJutsu\Bazooka\BazookaServiceProvider"
```

---

## Configuration

After publishing the configuration file, you can customize the behavior of Bazooka by editing `config/bazooka.php`:

```php
return [
    'enabled' => env('BAZOOKA_ENABLED', true), // Enable or disable Bazooka globally
    'probability' => 0.2, // Probability of chaos being injected or triggered (0 to 1)
    'strategies' => [
        'latency' => [
            'min_delay' => 100, // Minimum delay in milliseconds
            'max_delay' => 500, // Maximum delay in milliseconds
        ],
        'exception' => [
            'exceptions' => [
                \RuntimeException::class => 'Something went wrong!', // Exception class and message
            ],
        ],
    ],
];
```

---

## Usage

### Inject Chaos Points

Inject chaos points into your controller methods:

```bash
php artisan bazooka:inject
```

You can target specific controllers:

```bash
php artisan bazooka:inject --controller=TestController
```

### List Chaos Points

List all chaos points in your application:

```bash
php artisan bazooka:list
```

Example output:

```
+-----------------------------------+------+--------+
| File                              | Line | Method |
+-----------------------------------+------+--------+
| Http/Controllers/TestController.php | 10   | index  |
+-----------------------------------+------+--------+
```

### Remove Chaos Points

Remove all chaos points from your codebase:

```bash
php artisan bazooka:remove
```

Use the `--dry-run` option to preview changes without modifying files:

```bash
php artisan bazooka:remove --dry-run
```

---

## Disruption Strategies

Bazooka comes with built-in disruption strategies. You can configure them in `config/bazooka.php`.

### Latency Disruption

Add a random delay to your application:

```php
Bazooka::chaos(); // Adds a delay between min_delay and max_delay
```

### Exception Disruption

Throw a configured exception:

```php
Bazooka::chaos(); // Throws a RuntimeException with the configured message
```

---

## Example

### Controller Before Injection

```php
namespace App\Http\Controllers;

class TestController
{
    public function index()
    {
        return 'Hello World';
    }
}
```

### Controller After Injection

```php
namespace App\Http\Controllers;

class TestController
{
    public function index()
    {
        Bazooka::chaos(); // Chaos point injected here
        return 'Hello World';
    }
}
```

---

## Testing

Run the test suite to ensure everything works as expected:

```bash
composer test
```

---

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

---

## License

This package is open-source software licensed under the [MIT License](LICENSE).

---

## Credits

- Developed by [ludo](https://github.com/ludoguenet)
- Inspired by the need for chaos engineering in Laravel applications.

---

Enjoy injecting chaos into your Laravel applications with **Laravel Bazooka**! 🎉
