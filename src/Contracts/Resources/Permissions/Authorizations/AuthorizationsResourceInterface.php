<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources\Permissions\Authorizations;

use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Requests\Permissions\Authorizations\Grants\GrantsRequest;

interface AuthorizationsResourceInterface
{
    /**
     * @param GrantsRequest|array<string, mixed> $request
     */
    public function grants(GrantsRequest | array $request): ResponseInterface;
}
