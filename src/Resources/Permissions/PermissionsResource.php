<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Permissions;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Authorizations\AuthorizationsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Common\CommonResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Entities\EntitiesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\EuEntities\EuEntitiesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Indirect\IndirectResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Operations\OperationsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\PermissionsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Persons\PersonsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Query\QueryResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\Subunits\SubunitsResourceInterface;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Permissions\Authorizations\AuthorizationsResource;
use N1ebieski\KSEFClient\Resources\Permissions\Common\CommonResource;
use N1ebieski\KSEFClient\Resources\Permissions\Entities\EntitiesResource;
use N1ebieski\KSEFClient\Resources\Permissions\EuEntities\EuEntitiesResource;
use N1ebieski\KSEFClient\Resources\Permissions\Indirect\IndirectResource;
use N1ebieski\KSEFClient\Resources\Permissions\Operations\OperationsResource;
use N1ebieski\KSEFClient\Resources\Permissions\Persons\PersonsResource;
use N1ebieski\KSEFClient\Resources\Permissions\Query\QueryResource;
use N1ebieski\KSEFClient\Resources\Permissions\Subunits\SubunitsResource;
use Throwable;

final class PermissionsResource extends AbstractResource implements PermissionsResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
    }

    public function common(): CommonResourceInterface
    {
        try {
            return new CommonResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function persons(): PersonsResourceInterface
    {
        try {
            return new PersonsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function entities(): EntitiesResourceInterface
    {
        try {
            return new EntitiesResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function authorizations(): AuthorizationsResourceInterface
    {
        try {
            return new AuthorizationsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function indirect(): IndirectResourceInterface
    {
        try {
            return new IndirectResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function subunits(): SubunitsResourceInterface
    {
        try {
            return new SubunitsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function euEntities(): EuEntitiesResourceInterface
    {
        try {
            return new EuEntitiesResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function operations(): OperationsResourceInterface
    {
        try {
            return new OperationsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function query(): QueryResourceInterface
    {
        try {
            return new QueryResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }
}
