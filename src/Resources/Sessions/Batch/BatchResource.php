<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Sessions\Batch;

use N1ebieski\KSEFClient\Actions\EncryptDocument\EncryptDocumentHandler;
use N1ebieski\KSEFClient\Actions\SplitDocumentIntoParts\SplitDocumentIntoPartsHandler;
use N1ebieski\KSEFClient\Actions\ValidateXml\ValidateXmlHandler;
use N1ebieski\KSEFClient\Actions\ZipDocuments\ZipDocumentsHandler;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\Batch\BatchResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Exceptions\ExceptionHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\Close\CloseHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\Close\CloseRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendHandler;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendXmlRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendZipRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use Psr\Log\LoggerInterface;

final class BatchResource extends AbstractResource implements BatchResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function openAndSend(OpenAndSendRequest | OpenAndSendXmlRequest | OpenAndSendZipRequest | array $request): ResponseInterface
    {
        if (is_array($request)) {
            $request = OpenAndSendRequest::from($request);
        }

        return (new OpenAndSendHandler(
            client: $this->client,
            encryptDocument: new EncryptDocumentHandler($this->logger),
            zipDocuments: new ZipDocumentsHandler(),
            splitDocumentIntoParts: new SplitDocumentIntoPartsHandler(),
            validateXml: new ValidateXmlHandler(),
            exceptionHandler: new ExceptionHandler($this->logger),
            config: $this->config
        ))->handle($request);
    }

    public function close(CloseRequest | array $request): ResponseInterface
    {
        if ($request instanceof CloseRequest === false) {
            $request = CloseRequest::from($request);
        }

        return (new CloseHandler($this->client))->handle($request);
    }
}
