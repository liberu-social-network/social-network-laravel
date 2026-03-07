<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Reset static service provider state before each test so that
     * Fortify and Jetstream routes are properly registered even after
     * Filament panel providers set $registersRoutes = false.
     */
    protected function setUp(): void
    {
        Fortify::$registersRoutes = true;
        Jetstream::$registersRoutes = true;

        parent::setUp();
    }
}
