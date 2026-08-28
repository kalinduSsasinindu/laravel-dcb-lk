<?php

declare(strict_types=1);

namespace DcbLk;

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
 */
class DcbManager
{
    /** @var array<string, CarrierDriver> */
    protected array $drivers = [];

    public function __construct(protected Application $app)
    {
    }

    public function driver(?string $name = null): CarrierDriver
    {
        $name ??= $this->app['config']['dcb-lk.default'];

        return $this->drivers[$name] ??= $this->resolve($name);
    }

    protected function resolve(string $name): CarrierDriver
    {
        $config = $this->app['config']["dcb-lk.drivers.{$name}"];

        if ($config === null) {
            throw new InvalidArgumentException("No DCB-LK driver configured for [{$name}] - check config/dcb-lk.php.");
        }

        return match ($name) {
            'ideamart' => new IdeamartDriver($config),
            'mspace' => new MSpaceDriver($config),
            default => throw new InvalidArgumentException("Unsupported DCB-LK driver [{$name}] - only \"ideamart\" and \"mspace\" are built in."),
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
