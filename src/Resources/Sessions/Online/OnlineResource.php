<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Sessions\Online;

use N1ebieski\KSEFClient\Actions\EncryptDocument\EncryptDocumentHandler;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\Online\OnlineResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Close\CloseHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Close\CloseRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Invoices\InvoicesHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Invoices\InvoicesRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Invoices\InvoicesXmlRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Open\OpenHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Open\OpenRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use Psr\Log\LoggerInterface;

final class OnlineResource extends AbstractResource implements OnlineResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function open(OpenRequest | array $request): ResponseInterface
    {
        if ($request instanceof OpenRequest === false) {
            $request = OpenRequest::from($request);
        }

        return new OpenHandler($this->client)->handle($request);
    }

    public function close(CloseRequest | array $request): ResponseInterface
    {
        if ($request instanceof CloseRequest === false) {
            $request = CloseRequest::from($request);
        }

        return new CloseHandler($this->client)->handle($request);
    }

    public function invoices(InvoicesRequest | InvoicesXmlRequest | array $request): ResponseInterface
    {
        if (is_array($request)) {
            $request = InvoicesRequest::from($request);
        }

        return new InvoicesHandler(
            client: $this->client,
            encryptDocument: new EncryptDocumentHandler($this->logger),
            config: $this->config
        )->handle($request);
    }
}
