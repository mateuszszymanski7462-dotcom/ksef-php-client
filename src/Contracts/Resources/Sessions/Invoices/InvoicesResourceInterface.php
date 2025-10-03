<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources\Sessions\Invoices;

use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Status\StatusRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo\UpoRequest;

interface InvoicesResourceInterface
{
    /**
     * @param StatusRequest|array<string, mixed> $request
     */
    public function status(StatusRequest | array $request): ResponseInterface;

    /**
     * @param KsefUpoRequest|array<string, mixed> $request
     */
    public function ksefUpo(KsefUpoRequest | array $request): ResponseInterface;

    /**
     * @param UpoRequest|array<string, mixed> $request
     */
    public function upo(UpoRequest | array $request): ResponseInterface;
}
