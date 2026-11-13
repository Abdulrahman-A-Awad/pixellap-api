<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    public const REVALIDATE_URL = 'http://frontend.test/api/revalidate';

    protected function setUp(): void
    {
        parent::setUp();

        // Content saves queue a frontend revalidation (sync queue in tests):
        // never let it reach a real server.
        config([
            'pixellap.frontend_revalidate_url' => self::REVALIDATE_URL,
            'pixellap.revalidate_secret' => 'test-secret',
        ]);
        Http::preventStrayRequests();
        Http::fake([self::REVALIDATE_URL => Http::response(['revalidated' => true])]);
    }
}
