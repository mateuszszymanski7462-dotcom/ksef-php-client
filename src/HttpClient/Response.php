<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\HttpClient;

use JsonException;
use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Factories\ExceptionFactory;
use N1ebieski\KSEFClient\Support\Arr;
use N1ebieski\KSEFClient\ValueObjects\Support\KeyType;
use Psr\Http\Message\ResponseInterface as BaseResponseInterface;

final class Response implements ResponseInterface
{
    private readonly string $contents;

    private readonly int $statusCode;

    public function __construct(
        public readonly BaseResponseInterface $baseResponse,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
        $this->contents = $baseResponse->getBody()->getContents();
        $this->statusCode = $baseResponse->getStatusCode();

        $this->throwExceptionIfError();
    }

    private function throwExceptionIfError(): void
    {
        if ($this->statusCode < 400) {
            return;
        }

        try {
            $exceptionResponse = $this->contents === '' ? null : $this->object();
        } catch (JsonException) {
            $exceptionResponse = null;
        }

        $this->exceptionHandler->handle(
            //@phpstan-ignore-next-line
            ExceptionFactory::make($this->statusCode, $exceptionResponse)
        );
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->contents;
    }

    public function object(): object | array
    {
        /** @var object|array<string, mixed> */
        return json_decode($this->contents, flags: JSON_THROW_ON_ERROR);
    }

    public function json(): array
    {
        /** @var array<string, mixed> */
        return json_decode($this->contents, true, flags: JSON_THROW_ON_ERROR);
    }

    public function toArray(KeyType $keyType = KeyType::Camel, array $only = []): array
    {
        /** @var array<string, mixed> */
        return Arr::normalize([
            'statusCode' => $this->statusCode,
            'contents' => $this->contents,
        ], $keyType, $only);
    }
}
