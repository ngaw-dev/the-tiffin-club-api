<?php

namespace Tests;

use Database\Seeders\GlobalSettingSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Not every test migrates (e.g. ExampleTest); seed only then.
        if (Schema::hasTable('global_settings')) {
            $this->seed(GlobalSettingSeeder::class);
        }
    }
}
