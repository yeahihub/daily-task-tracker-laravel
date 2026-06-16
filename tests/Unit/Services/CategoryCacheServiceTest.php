<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Closure;
use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\CacheManager;
use App\Services\CategoryCacheService;
use PHPUnit\Framework\Attributes\Test;

class CategoryCacheServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_generates_correct_cache_key(): void
    {
        $cache   = Mockery::mock(CacheManager::class);
        $service = new CategoryCacheService($cache);

        $this->assertSame('categories.user.42', $service->getKey(42));
    }

    #[Test]
    public function it_remembers_value_in_cache(): void
    {
        $expectedResult = ['uuid-1' => 'Work', 'uuid-2' => 'Personal'];

        $cache = Mockery::mock(CacheManager::class);
        $cache->shouldReceive('remember')
            ->once()
            ->with('categories.user.1', 3600, Mockery::type(Closure::class))
            ->andReturn($expectedResult);

        $service = new CategoryCacheService($cache);

        $result = $service->remember(1, fn() => $expectedResult);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_clears_cache_for_user(): void
    {
        $cache = Mockery::mock(CacheManager::class);
        $cache->shouldReceive('forget')
            ->once()
            ->with('categories.user.5')
            ->andReturn(true);

        $service = new CategoryCacheService($cache);

        $result = $service->clear(5);

        $this->assertTrue($result);
    }
}
