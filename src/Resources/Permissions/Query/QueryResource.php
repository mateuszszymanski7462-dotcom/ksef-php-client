<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Permissions\Query;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Query\Personal\PersonalResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Query\QueryResourceInterface;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Permissions\Query\Personal\PersonalResource;
use Throwable;

final class QueryResource extends AbstractResource implements QueryResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
    }

    public function personal(): PersonalResourceInterface
    {
        try {
            return new PersonalResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }
}
