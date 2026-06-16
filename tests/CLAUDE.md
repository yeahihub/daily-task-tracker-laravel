# Testing Conventions

## Framework & Configuration

- **Test framework**: PHPUnit 11
- **Feature tests**: Extend `Tests\TestCase`
- **Unit tests**: Extend `PHPUnit\Framework\TestCase`
- **Database strategy**: Use `DatabaseTransactions` trait
- **Faker**: Use `WithFaker` trait and `$this->faker->...` or `fake()->...` — follow the convention used in the file you're editing

## Unit vs Feature Test Placement

- **Unit tests** are for pure logic that can be tested without the framework: enums, value objects, services where all dependencies can be mocked.
- **Feature tests** are for anything that touches the database, HTTP layer, etc.
- If an action/class requires `DatabaseTransactions` or `Tests\TestCase` to test, write a feature test instead. Do not boot the framework in unit tests.

## Test Naming

- Use `#[Test]` attribute — never `test_` prefix
- Name tests as readable sentences: `authenticated_user_can_view_tasks`, `guest_cannot_view_tasks`

## Test Patterns

### Feature Tests (Controllers)

Every controller test file should cover:

1. **Happy paths** — authenticated user can perform CRUD operations
2. **Guest access** — unauthenticated users are redirected to login
3. **Validation failures** — use `#[DataProvider]` with a static data provider method
4. **Authorization** — users cannot access/modify other users' resources (`assertForbidden()`)
5. **Data isolation** — users only see their own data
6. **Edge cases** — filtering, empty states, etc.

```php
#[Test]
public function authenticated_user_can_create_tasks(): void
{
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('tasks.store'), [...]);

    $response->assertRedirect(route('tasks.index'));
    $response->assertSessionHas('success', 'Task created successfully.');
    $this->assertDatabaseHas('tasks', [...]);
}
```

### Validation Data Providers

Use static methods returning named arrays:

```php
public static function invalidTaskDataProvider(): array
{
    return [
        'missing title' => [
            ['title' => '', 'task_date' => fake()->date()],
            'title',
        ],
    ];
}

#[Test]
#[DataProvider('invalidTaskDataProvider')]
public function task_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post(route('tasks.store'), $data);
    $response->assertInvalid($expectedErrorField);
}
```

### Feature Tests (Commands)

Use `$this->artisan()` with assertion chaining:

```php
$this->artisan('app:generate-recurring-tasks')
    ->expectsOutputToContain('Created 1 recurring tasks.')
    ->assertSuccessful();
```

Use `$this->travelTo()` to control the current date for time-dependent logic.

### Unit Tests (Pure Logic)

- Always extend `PHPUnit\Framework\TestCase` — never `Tests\TestCase`
- Use Mockery for dependencies on `readonly` classes — mock the underlying dependency instead (e.g., mock `CacheManager`, not the `readonly` service)
- Always call `Mockery::close()` in `tearDown()`
- Use `$this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount())` to avoid risky test warnings when the only assertions are Mockery expectations

## Factories

Always use factories. Check available states before manually setting attributes:

- **UserFactory**: `unverified()` — user with null `email_verified_at`
- **TaskFactory**: `completed()`, `withoutCategory()`, `today()`, `overdue()`, `forRecurringTask($recurringTask)`
- **RecurringTaskFactory**: `daily()`, `weekdays()`, `weekly(['monday'])`, `monthly(15)`, `withEndDate('+1 year')`
- **CategoryFactory**: no custom states

Use `->for($user)` to associate models: `Task::factory()->for($user)->create()`

## Route Testing

- Use named routes: `route('tasks.index')`, not `/tasks`
- Models use UUID route keys — pass the model directly to `route()`: `route('tasks.edit', $task)`
- Verified-only routes redirect unverified users to `route('verification.notice')`
- Guest routes redirect authenticated users to `route('dashboard')`

## Running Tests

```bash
# All tests
vendor/bin/sail artisan test --compact

# Single file
vendor/bin/sail artisan test --compact tests/Feature/Controllers/TaskControllerTest.php

# Single test method
vendor/bin/sail artisan test --compact --filter=authenticated_user_can_view_tasks
```

## Code Style

- Run `vendor/bin/sail bin pint --dirty --format agent` before finalizing
- Include `declare(strict_types=1);` at the top of every test file
- Use PHPUnit attributes (`#[Test]`, `#[DataProvider]`) — not annotations
