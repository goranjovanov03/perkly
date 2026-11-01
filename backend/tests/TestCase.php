<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $defaultConnection = config('database.default');
        $databaseName = config("database.connections.{$defaultConnection}.database");

        if ($databaseName === 'perkly' || app()->environment() !== 'testing') {
            throw new \RuntimeException(
                'Tests cannot run against the development database. Default connection is "' . $defaultConnection . '" with database "' . $databaseName . '" and environment is "' . app()->environment() . '".'
            );
        }
    }
}