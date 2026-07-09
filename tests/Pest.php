<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeatedly see in your test files. Here you create your own
| custom functions to use across your tests.
|
*/

function loadFixture(string $path): array
{
    $fixturePath = dirname(__DIR__) . '/Fixtures/data/api-data/' . $path;

    if (!file_exists($fixturePath)) {
        throw new RuntimeException("Fixture not found: {$fixturePath}");
    }

    return json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
}
