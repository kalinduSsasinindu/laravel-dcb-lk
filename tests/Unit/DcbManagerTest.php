<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit;

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
}
