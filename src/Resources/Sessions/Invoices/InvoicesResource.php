<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Sessions\Invoices;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\Invoices\InvoicesResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Status\StatusHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Status\StatusRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo\UpoHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo\UpoRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use Psr\Log\LoggerInterface;

final class InvoicesResource extends AbstractResource implements InvoicesResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function status(StatusRequest | array $request): ResponseInterface
    {
        if ($request instanceof StatusRequest === false) {
            $request = StatusRequest::from($request);
        }

        return new StatusHandler($this->client)->handle($request);
    }

    public function ksefUpo(KsefUpoRequest | array $request): ResponseInterface
    {
        if ($request instanceof KsefUpoRequest === false) {
            $request = KsefUpoRequest::from($request);
        }

        return new KsefUpoHandler($this->client)->handle($request);
    }

    public function upo(UpoRequest | array $request): ResponseInterface
    {
        if ($request instanceof UpoRequest === false) {
            $request = UpoRequest::from($request);
        }

        return new UpoHandler($this->client)->handle($request);
    }
}
