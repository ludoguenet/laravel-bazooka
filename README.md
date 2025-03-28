<p align="center"><img src="/art/logo-ghost.png" alt="Logo Laravel Bazooka" style="max-width: 100%;"></p>

<p align="center">
    <a href="https://packagist.org/packages/laraveljutsu/bazooka"><img src="https://img.shields.io/packagist/dt/laraveljutsu/bazooka" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/laraveljutsu/bazooka"><img src="https://img.shields.io/packagist/v/laraveljutsu/bazooka" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/laraveljutsu/bazooka"><img src="https://img.shields.io/packagist/l/laraveljutsu/bazooka" alt="License"></a>
</p>

## Introduction

**Inject chaos into your Laravel application with ease!**

Laravel Bazooka is a powerful Laravel package that allows you to inject controlled chaos into your application. Whether you're testing resilience, simulating failures, or just having fun, Bazooka provides a simple and configurable way to disrupt your application's behavior.

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
composer require laraveljutsu/bazooka
```

Laravel will automatically register the service provider.

If needed, add to config/app.php providers array:

```php
'providers' => [
    // ...
    LaravelJutsu\Bazooka\BazookaServiceProvider::class,
],
```

---

## Configuration

After publishing the configuration file, you can customize the behavior of Bazooka by editing `config/bazooka.php`:

To publish the configuration file, run the following command:

```bash
php artisan vendor:publish --provider="LaravelJutsu\Bazooka\BazookaServiceProvider" --tag="config"
```

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

Add a delay to your application.

### Exception Disruption

Throw a prepared exception.

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
        \LaravelJutsu\Bazooka\Facades\Bazooka::chaos(); // Chaos point injected here
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

- Developed by [ludo 🐆](https://twitter.com/LaravelJutsu)
- Inspired by the need for chaos engineering in Laravel applications.

---

Enjoy injecting chaos into your Laravel applications with **Laravel Bazooka**! 🎉
