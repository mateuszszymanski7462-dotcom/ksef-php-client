<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Factories;

use N1ebieski\KSEFClient\Exceptions\HttpClient\BadRequestException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\ClientException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\Exception;
use N1ebieski\KSEFClient\Exceptions\HttpClient\InternalServerException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\ServerException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\UnknownSystemException;
use N1ebieski\KSEFClient\Factories\AbstractFactory;
use N1ebieski\KSEFClient\Support\Utility;

final class ExceptionFactory extends AbstractFactory
{
    /**
     * @param object{exception: object{exceptionDetailList: array<int, object{exceptionCode: int, exceptionDescription: string}>}} $exceptionResponse
     */
    public static function make(
        int $statusCode,
        ?object $exceptionResponse
    ): Exception {
        $message = null;

        if ($exceptionResponse !== null) {
            $exceptions = $exceptionResponse->exception->exceptionDetailList;

            $firstException = $exceptions[0] ?? null;

            if ($firstException !== null) {
                $message = "{$firstException->exceptionCode} {$firstException->exceptionDescription}";
            }
        }

        /** @var class-string<Exception> $exceptionNamespace */
        $exceptionNamespace = match (true) {
            $statusCode === 400 => BadRequestException::class,
            $statusCode === 500 => InternalServerException::class,
            $statusCode === 501 => UnknownSystemException::class,
            $statusCode === 401 => Utility::value(function () use (&$message): string {
                $message ??= 'Unauthorized';

                return ClientException::class;
            }),
            $statusCode === 404 => Utility::value(function () use (&$message): string {
                $message ??= 'Not found';

                return ClientException::class;
            }),
            $statusCode > 400 && $statusCode < 500 => ClientException::class,
            $statusCode > 500 => ServerException::class,
            default => Exception::class
        };

        return new $exceptionNamespace(
            message: $message ?? '',
            code: $statusCode,
            context: $exceptionResponse
        );
    }
}
