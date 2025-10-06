<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Invoices\Exports;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Invoices\Exports\ExportsResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Init\InitHandler;
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Init\InitRequest;
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Status\StatusHandler;
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Status\StatusRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use Psr\Log\LoggerInterface;

final class ExportsResource extends AbstractResource implements ExportsResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function init(InitRequest | array $request): ResponseInterface
    {
        if ($request instanceof InitRequest === false) {
            $request = InitRequest::from($request);
        }

        return new InitHandler($this->client)->handle($request);
    }

    public function status(StatusRequest | array $request): ResponseInterface
    {
        if ($request instanceof StatusRequest === false) {
            $request = StatusRequest::from($request);
        }

        return new StatusHandler($this->client)->handle($request);
    }
}
