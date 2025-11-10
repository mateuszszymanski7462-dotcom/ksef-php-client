<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Testdata;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Limits\LimitsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Person\PersonResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Subject\SubjectResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\TestdataResourceInterface;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Testdata\Limits\LimitsResource;
use N1ebieski\KSEFClient\Resources\Testdata\Person\PersonResource;
use N1ebieski\KSEFClient\Resources\Testdata\Subject\SubjectResource;
use Throwable;

final class TestdataResource extends AbstractResource implements TestdataResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
    }

    public function subject(): SubjectResourceInterface
    {
        try {
            return new SubjectResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function person(): PersonResourceInterface
    {
        try {
            return new PersonResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function limits(): LimitsResourceInterface
    {
        try {
            return new LimitsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }
}
