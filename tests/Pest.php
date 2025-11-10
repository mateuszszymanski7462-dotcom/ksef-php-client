<?php

namespace N1ebieski\KSEFClient\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Contracts\ValueAwareInterface;
use N1ebieski\KSEFClient\Exceptions\HttpClient\BadRequestException;
use N1ebieski\KSEFClient\Tests\Feature\AbstractTestCase as FeatureAbstractTestCase;
use N1ebieski\KSEFClient\Tests\Unit\AbstractTestCase as UnitAbstractTestCase;
use N1ebieski\KSEFClient\ValueObjects\Mode;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

uses(UnitAbstractTestCase::class)->in('Unit');
uses(FeatureAbstractTestCase::class)
    ->beforeEach(function (): void {
        $client = (new ClientBuilder())
            ->withMode(Mode::Test)
            ->build();

        try {
            $client->testdata()->person()->create([
                'nip' => $_ENV['NIP_1'],
                'pesel' => $_ENV['PESEL_1'],
                'isBailiff' => false,
                'description' => 'testing',
            ]);
        } catch (BadRequestException $exception) {
            if (str_starts_with($exception->getMessage(), '30001')) {
                // ignore
            }
        }
    })
    ->afterAll(function (): void {
        $client = (new ClientBuilder())
            ->withMode(Mode::Test)
            ->build();

        $client->testdata()->person()->remove([
            'nip' => $_ENV['NIP_1'],
        ]);
    })
    ->in('Feature');

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

/**
* @param array<string, mixed> $data
*/
//@phpstan-ignore-next-line
expect()->extend('toBeFixture', fn (array $data) => toBeFixture($data, $this->value));

expect()->extend('toBeExceptionFixture', function (array $data): void {
    /** @var array{exception: array{exceptionCode: string, exceptionDescription: string, exceptionDetailList: array<array{exceptionCode: string, exceptionDescription: string}>}} $data */
    $firstException = $data['exception']['exceptionDetailList'][0];

    //@phpstan-ignore-next-line
    expect($this->value)->toThrow(new BadRequestException(
        message: "{$firstException['exceptionCode']} {$firstException['exceptionDescription']}",
        code: 400,
        context: (object) $data
    ));
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param array<string, mixed> $data
 */
function toBeFixture(array $data, ?object $object = null): void
{
    foreach ($data as $key => $value) {
        expect($object)->toHaveProperty($key);

        if (is_array($value) && is_array($object->{$key}) && isset($object->{$key}[0]) && is_object($object->{$key}[0])) {
            foreach ($object->{$key} as $itemKey => $itemValue) {
                if (is_string($value[$itemKey])) {
                    $value[$itemKey] = ['value' => $value[$itemKey]];
                }

                /**
                 * @var array<string, array<string, mixed>> $value
                 * @var string $itemKey
                 * @var object $itemValue
                 */
                toBeFixture($value[$itemKey], $itemValue);
            }

            continue;
        }

        if (is_array($value) && is_object($object->{$key})) {
            /** @var array<string, mixed> $value */
            toBeFixture($value, $object->{$key});

            continue;
        }

        $expected = match (true) {
            //@phpstan-ignore-next-line
            $object->{$key} instanceof DateTimeInterface => new DateTimeImmutable($value),
            //@phpstan-ignore-next-line
            $object->{$key} instanceof ValueAwareInterface && $object->{$key}->value instanceof DateTimeInterface => new DateTimeImmutable($value),
            default => $value,
        };

        $actual = match (true) {
            $object->{$key} instanceof DateTimeInterface => $object->{$key},
            $object->{$key} instanceof ValueAwareInterface => $object->{$key}->value,
            default => $object->{$key},
        };

        expect($actual)->toEqual($expected);
    }
}
