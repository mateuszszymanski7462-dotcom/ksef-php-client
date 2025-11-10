<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Permissions\EuEntities;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\EuEntities\Administration\AdministrationResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\EuEntities\EuEntitiesResourceInterface;
use N1ebieski\KSEFClient\Requests\Permissions\EuEntities\Grants\GrantsHandler;
use N1ebieski\KSEFClient\Requests\Permissions\EuEntities\Grants\GrantsRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Permissions\EuEntities\Administration\AdministrationResource;
use Throwable;

final class EuEntitiesResource extends AbstractResource implements EuEntitiesResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
    }

    public function administration(): AdministrationResourceInterface
    {
        try {
            return new AdministrationResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
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
