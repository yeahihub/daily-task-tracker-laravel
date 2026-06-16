<?php

declare(strict_types=1);

namespace Tests\Unit\Enum;

use App\Enums\TaskFrequency;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class TaskFrequencyTest extends TestCase
{
    public static function frequenciesThatReturnNullConfig(): array
    {
        return ['daily' => [TaskFrequency::Daily], 'weekdays' => [TaskFrequency::Weekdays]];
    }

    #[Test]
    #[DataProvider('frequenciesThatReturnNullConfig')]
    public function frequency_returns_null_config(TaskFrequency $frequency): void
    {
        $config = $frequency->buildConfig([]);

        $this->assertNull($config);
    }

    #[Test]
    public function weekly_frequency_returns_null_without_days(): void
    {
        $config1 = TaskFrequency::Weekly->buildConfig([]);
        $config2 = TaskFrequency::Weekly->buildConfig(['foo' => 'bar']);

        $this->assertNull($config1);
        $this->assertNull($config2);
    }

    #[Test]
    public function weekly_frequency_builds_config_with_days(): void
    {
        $config = TaskFrequency::Weekly->buildConfig(['days' => ['monday', 'wednesday', 'friday']]);

        $this->assertSame(['days' => ['monday', 'wednesday', 'friday']], $config);
    }

    #[Test]
    public function monthly_frequency_builds_config_with_day_of_month(): void
    {
        $config = TaskFrequency::Monthly->buildConfig(['day_of_month' => 15]);

        $this->assertSame(['day_of_month' => 15], $config);
    }

    #[Test]
    public function monthly_frequency_returns_null_without_day_of_month(): void
    {
        $config1 = TaskFrequency::Monthly->buildConfig([]);
        $config2 = TaskFrequency::Monthly->buildConfig(['foo' => 'bar']);

        $this->assertNull($config1);
        $this->assertNull($config2);
    }

    #[Test]
    public function monthly_frequency_casts_day_of_month_to_int(): void
    {
        $config = TaskFrequency::Monthly->buildConfig(['day_of_month' => '15']);

        $this->assertSame(['day_of_month' => 15], $config);
    }

    #[Test]
    public function it_has_exactly_four_cases(): void
    {
        $this->assertCount(4, TaskFrequency::cases());
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $this->assertSame(TaskFrequency::Daily, TaskFrequency::from('daily'));
        $this->assertSame(TaskFrequency::Weekdays, TaskFrequency::from('weekdays'));
        $this->assertSame(TaskFrequency::Weekly, TaskFrequency::from('weekly'));
        $this->assertSame(TaskFrequency::Monthly, TaskFrequency::from('monthly'));
    }

    #[Test]
    #[DataProvider('frequenciesThatReturnNullConfig')]
    public function daily_and_weekdays_always_return_null_even_with_extra_data(TaskFrequency $frequency): void
    {
        $config = $frequency->buildConfig(['days' => ['monday'], 'day_of_month' => 15]);

        $this->assertNull($config);
    }
}
