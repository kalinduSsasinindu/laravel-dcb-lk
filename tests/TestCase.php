<?php

declare(strict_types=1);

namespace DcbLk\Tests;

use DcbLk\DcbLkServiceProvider;
use DcbLk\Facades\DcbLk;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [DcbLkServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['DcbLk' => DcbLk::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dcb-lk.drivers.ideamart.app_id', 'test-app-id');
        $app['config']->set('dcb-lk.drivers.ideamart.password', 'test-password');
        $app['config']->set('dcb-lk.drivers.ideamart.base_url', 'https://api.ideamart.io/subscription');

        $app['config']->set('dcb-lk.drivers.mspace.app_id', 'test-app-id');
        $app['config']->set('dcb-lk.drivers.mspace.password', 'test-password');
        $app['config']->set('dcb-lk.drivers.mspace.base_url', 'https://api.mspace.lk');
    }
}
