<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend;

use N1ebieski\KSEFClient\Actions\EncryptDocument\EncryptDocumentAction;
use N1ebieski\KSEFClient\Actions\EncryptDocument\EncryptDocumentHandler;
use N1ebieski\KSEFClient\Actions\SplitDocumentIntoParts\SplitDocumentIntoPartsAction;
use N1ebieski\KSEFClient\Actions\SplitDocumentIntoParts\SplitDocumentIntoPartsHandler;
use N1ebieski\KSEFClient\Actions\ZipDocuments\ZipDocumentsAction;
use N1ebieski\KSEFClient\Actions\ZipDocuments\ZipDocumentsHandler;
use N1ebieski\KSEFClient\Contracts\ConfigInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\DTOs\HttpClient\Request;
use N1ebieski\KSEFClient\DTOs\Requests\Sessions\Faktura;
use N1ebieski\KSEFClient\Requests\AbstractHandler;
use N1ebieski\KSEFClient\ValueObjects\EncryptionKey;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\Method;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\Uri;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;
use RuntimeException;

final class OpenAndSendHandler extends AbstractHandler
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly EncryptDocumentHandler $encryptDocumentHandler,
        private readonly ZipDocumentsHandler $zipDocumentsHandler,
        private readonly SplitDocumentIntoPartsHandler $splitDocumentIntoPartsHandler,
        private readonly Config $config
    ) {
    }

    public function handle(OpenAndSendRequest | OpenAndSendXmlRequest | OpenAndSendZipRequest $request): ResponseInterface
    {
        if ($this->config->encryptionKey instanceof EncryptionKey === false) {
            throw new RuntimeException('Encryption key is required to send invoice.');
        }

        if ($this->config->encryptedKey instanceof EncryptedKey === false) {
            throw new RuntimeException('Encrypted key is required to open session.');
        }

        $documents = match (true) {
            $request instanceof OpenAndSendRequest => array_map(
                fn (Faktura $faktura): string => $faktura->toXml(),
                $request->faktury
            ),
            default => $request->faktury,
        };

        $zipDocument = is_array($documents)
            ? $this->zipDocumentsHandler->handle(new ZipDocumentsAction($documents))
            : $documents;

        $fileSize = strlen($zipDocument);

        if ($fileSize > ConfigInterface::BATCH_MAX_FILE_SIZE) {
            throw new RuntimeException('File size is too big.');
        }

        $parts = $this->splitDocumentIntoPartsHandler->handle(new SplitDocumentIntoPartsAction(
            document: $zipDocument,
            partSize: ConfigInterface::BATCH_MAX_PART_SIZE
        ));

        $encryptedParts = [];

        foreach ($parts as $part) {
            $encryptedParts[] = $this->encryptDocumentHandler->handle(new EncryptDocumentAction(
                encryptionKey: $this->config->encryptionKey,
                document: $part
            ));
        }

        $openResponse = $this->client->sendRequest(new Request(
            method: Method::Post,
            uri: Uri::from('sessions/batch'),
            body: [
                ...$request->toBody(),
                'batchFile' => [
                    'fileSize' => $fileSize,
                    'fileHash' => base64_encode(hash('sha256', $zipDocument, true)),
                    'fileParts' => array_map(function (int $index, string $encryptedPart): array {
                        $fileName = uniqid('part_', true) . '.zip.aes';

                        return [
                            'ordinalNumber' => $index + 1,
                            'fileName' => $fileName,
                            'fileSize' => strlen($encryptedPart),
                            'fileHash' => base64_encode(hash('sha256', $encryptedPart, true)),
                        ];
                    }, array_keys($encryptedParts), $encryptedParts),
                ],
                'encryption' => [
                    'encryptedSymmetricKey' => $this->config->encryptedKey->key,
                    'initializationVector' => $this->config->encryptedKey->iv
                ]
            ]
        ));

        /** @var object{referenceNumber: string, partUploadRequests: array<int, object{ordinalNumber: int, method: string, url: string, headers: array<string, string>}>} */
        $openResponseToObject = $openResponse->object();

        $this->client
            ->withoutAccessToken()
            ->sendAsyncRequest(array_map(fn (object $partUploadRequest): Request => new Request(
                method: Method::from($partUploadRequest->method),
                uri: Uri::from($partUploadRequest->url),
                headers: (array) $partUploadRequest->headers,
                body: $encryptedParts[$partUploadRequest->ordinalNumber - 1],
            ), $openResponseToObject->partUploadRequests));

        return $openResponse;
    }
}
