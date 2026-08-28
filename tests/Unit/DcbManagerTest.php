<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit;

use DcbLk\Contracts\CarrierDriver;
use DcbLk\Data\CarrierResponse;
use DcbLk\DcbManager;
use DcbLk\Drivers\IdeamartDriver;
use DcbLk\Drivers\MSpaceDriver;
use DcbLk\Facades\DcbLk;
use DcbLk\Tests\TestCase;
use InvalidArgumentException;

final class DcbManagerTest extends TestCase
{
    public function test_it_resolves_the_default_driver(): void
    {
        $this->assertInstanceOf(IdeamartDriver::class, $this->app->make(DcbManager::class)->driver());
    }

    public function test_it_resolves_a_named_driver(): void
    {
        $manager = $this->app->make(DcbManager::class);

        $this->assertInstanceOf(IdeamartDriver::class, $manager->driver('ideamart'));
        $this->assertInstanceOf(MSpaceDriver::class, $manager->driver('mspace'));
    }

    public function test_it_caches_resolved_drivers(): void
    {
        $manager = $this->app->make(DcbManager::class);

        $this->assertSame($manager->driver('ideamart'), $manager->driver('ideamart'));
    }

    public function test_an_unknown_driver_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(DcbManager::class)->driver('unknown-provider');
    }

    public function test_the_facade_resolves_to_the_manager(): void
    {
        $this->assertInstanceOf(IdeamartDriver::class, DcbLk::driver());
    }

    public function test_extend_registers_a_driver_that_isnt_built_in(): void
    {
        $manager = $this->app->make(DcbManager::class);

        $manager->extend('dialog', fn ($app, $config) => new FakeCarrierDriver($config));

        $driver = $manager->driver('dialog');

        $this->assertInstanceOf(FakeCarrierDriver::class, $driver);
    }

    public function test_extend_can_override_a_built_in_driver(): void
    {
        $manager = $this->app->make(DcbManager::class);

        $manager->extend('ideamart', fn ($app, $config) => new FakeCarrierDriver($config));

        $this->assertInstanceOf(FakeCarrierDriver::class, $manager->driver('ideamart'));
    }

    public function test_extend_receives_that_drivers_own_config_slice(): void
    {
        $this->app['config']->set('dcb-lk.drivers.dialog', ['app_id' => 'dialog-app-id']);
        $manager = $this->app->make(DcbManager::class);

        $manager->extend('dialog', fn ($app, $config) => new FakeCarrierDriver($config));

        $this->assertSame('dialog-app-id', $manager->driver('dialog')->config['app_id']);
    }

    public function test_extend_overriding_a_resolved_driver_replaces_the_cached_instance(): void
    {
        $manager = $this->app->make(DcbManager::class);

        $first = $manager->driver('ideamart');
        $manager->extend('ideamart', fn ($app, $config) => new FakeCarrierDriver($config));

        $this->assertNotSame($first, $manager->driver('ideamart'));
        $this->assertInstanceOf(FakeCarrierDriver::class, $manager->driver('ideamart'));
    }
}

final class FakeCarrierDriver implements CarrierDriver
{
    public function __construct(public array $config = [])
    {
    }

    public function send(string $subscriberId, bool $register): CarrierResponse
    {
        return CarrierResponse::fromArray(['statusCode' => 'S1000']);
    }

    public function getStatus(string $subscriberId): CarrierResponse
    {
        return CarrierResponse::fromArray(['statusCode' => 'S1000']);
    }

    public function requestOtp(string $subscriberId, array $metadata = [], ?string $appHash = null): CarrierResponse
    {
        return CarrierResponse::fromArray(['statusCode' => 'S1000']);
    }

    public function verifyOtp(string $referenceNo, string $otp): CarrierResponse
    {
        return CarrierResponse::fromArray(['statusCode' => 'S1000']);
    }

    public function sendSms(string $subscriberId, string $message): CarrierResponse
    {
        return CarrierResponse::fromArray(['statusCode' => 'S1000']);
    }
}
