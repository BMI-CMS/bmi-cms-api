<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run tests against a non-test database.
     *
     * Runs before traits such as RefreshDatabase touch the database.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if (!str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "Refusing to run tests on database [{$database}]. Tests must use a database ending in \"_testing\" (see phpunit.xml)."
            );
        }

        return $app;
    }
}
