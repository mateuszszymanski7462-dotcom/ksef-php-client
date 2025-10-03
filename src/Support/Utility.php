<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Support;

use Closure;
use RuntimeException;
use SensitiveParameter;

final class Utility
{
    public static function retry(Closure $closure, int $backoff = 10, int $retryUntil = 120): mixed
    {
        $seconds = 0;

        while (true) {
            $result = $closure();

            if ($result !== null) {
                return $result;
            }

            $seconds += $backoff;

            if ($seconds > $retryUntil) {
                throw new RuntimeException("Operation did not return a result after retrying for {$retryUntil} seconds.");
            }

            sleep($backoff);
        }
    }

    /**
     * @return array{hashSHA: array{algorithm: string, encoding: string, value: string}, fileSize: int}
     */
    public static function hash(
        #[SensitiveParameter]
        string $document,
    ): array {
        $hashSHA = base64_encode(hash('sha256', $document, true));
        $fileSize = strlen($document);

        return [
            'hashSHA' => [
                'algorithm' => 'SHA-256',
                'encoding' => 'Base64',
                'value' => $hashSHA,
            ],
            'fileSize' => $fileSize,
        ];
    }

    /**
     * Get normalized path, like realpath() for non-existing path or file
     */
    public static function normalizePath(string $path): string
    {
        return array_reduce(explode('/', $path), function ($a, $b) {
            if ($a === null) {
                $a = "/";
            }
            if ($b === "" || $b === ".") {
                return $a;
            }
            if ($b === "..") {
                return dirname($a);
            }

            return preg_replace("/\/+/", "/", "$a/$b");
        });
    }

    /**
     * Return the default value of the given value.
     *
     * @template TValue
     * @template TArgs
     *
     * @param TValue|Closure(TArgs):TValue $value
     * @param  TArgs  ...$args
     * @return TValue
     */
    public static function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
