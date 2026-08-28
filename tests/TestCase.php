<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function portalUrl(string $slug, string $path = ''): string
    {
        return "http://{$slug}.".config('app.central_domain').'/portal'.$path;
    }
}
