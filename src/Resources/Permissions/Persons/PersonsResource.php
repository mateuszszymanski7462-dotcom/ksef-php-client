<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Permissions\Persons;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Persons\PersonsResourceInterface;
use N1ebieski\KSEFClient\Requests\Permissions\Persons\Grants\GrantsHandler;
use N1ebieski\KSEFClient\Requests\Permissions\Persons\Grants\GrantsRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use Throwable;

final class PersonsResource extends AbstractResource implements PersonsResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
    }

    public function grants(GrantsRequest | array $request): ResponseInterface
    {
        try {
            if ($request instanceof GrantsRequest === false) {
                $request = GrantsRequest::from($request);
            }

            return (new GrantsHandler($this->client))->handle($request);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }
}
