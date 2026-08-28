<?php

declare(strict_types=1);

namespace DcbLk;

use Closure;
use DcbLk\Contracts\CarrierDriver;
use DcbLk\Drivers\IdeamartDriver;
use DcbLk\Drivers\MSpaceDriver;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * DcbLk::driver('ideamart') / DcbLk::driver('mspace'), or just DcbLk::...
 * for config('dcb-lk.default') - same shape as Laravel's own Socialite/
 * Cashier manager pattern. Drivers are resolved once and cached per name
 * for the life of the request.
 *
 * Built-in support is Ideamart and mSpace, but this isn't closed for
 * extension: register any other CarrierDriver (a different DCB provider, a
 * v2/alternate implementation of an existing one, a test double) via
 * extend() from your AppServiceProvider::boot() - no fork of this package
 * required:
 *
 * <?php
 * DcbLk::extend('dialog', function (Application $app, array $config) {
 *     return new DialogDriver($config);
 * });
 *
 * Then set DCB_LK_DRIVER=dialog and add a "dialog" entry under
 * dcb-lk.drivers in your own published config - the closure receives
 * whatever's there (or [] if you keep it out of the shipped config
 * entirely and read your own env vars inside the closure instead).
 * extend() also overrides a built-in name, e.g. to swap in your own
 * IdeamartDriver subclass without touching this class.
 */
class DcbManager
{
    /** @var array<string, CarrierDriver> */
    protected array $drivers = [];

    /** @var array<string, Closure(Application, array<string, mixed>): CarrierDriver> */
    protected array $customCreators = [];

    public function __construct(protected Application $app)
    {
    }

    public function driver(?string $name = null): CarrierDriver
    {
        $name ??= $this->getDefaultDriver();

        return $this->drivers[$name] ??= $this->resolve($name);
    }

    /**
     * Register a custom driver resolver, or override a built-in one.
     *
     * @param  Closure(Application, array<string, mixed>): CarrierDriver  $callback
     */
    public function extend(string $name, Closure $callback): static
    {
        $this->customCreators[$name] = $callback;
        unset($this->drivers[$name]);

        return $this;
    }

    public function getDefaultDriver(): string
    {
        return $this->app['config']['dcb-lk.default'];
    }

    protected function resolve(string $name): CarrierDriver
    {
        $config = $this->app['config']["dcb-lk.drivers.{$name}"] ?? [];

        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this->app, $config);
        }

        if ($config === []) {
            throw new InvalidArgumentException("No DCB-LK driver configured for [{$name}] - check config/dcb-lk.php.");
        }

        return match ($name) {
            'ideamart' => new IdeamartDriver($config),
            'mspace' => new MSpaceDriver($config),
            default => throw new InvalidArgumentException(
                "Unsupported DCB-LK driver [{$name}] - only \"ideamart\" and \"mspace\" are built in. "
                . "Register a custom one via DcbLk::extend('{$name}', ...)."
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
