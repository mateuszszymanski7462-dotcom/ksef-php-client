<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources\Sessions;

use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\Batch\BatchResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\Invoices\InvoicesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\Online\OnlineResourceInterface;
use N1ebieski\KSEFClient\Requests\Sessions\Status\StatusRequest;

interface SessionsResourceInterface
{
    public function online(): OnlineResourceInterface;

    public function batch(): BatchResourceInterface;

    /**
     * @param StatusRequest|array<string, mixed> $request
     */
    public function status(StatusRequest | array $request): ResponseInterface;

    public function invoices(): InvoicesResourceInterface;
}
