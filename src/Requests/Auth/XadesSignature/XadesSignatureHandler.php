<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Auth\XadesSignature;

use N1ebieski\KSEFClient\Actions\SignDocument\SignDocumentAction;
use N1ebieski\KSEFClient\Actions\SignDocument\SignDocumentHandler;
use N1ebieski\KSEFClient\Actions\ValidateXml\ValidateXmlAction;
use N1ebieski\KSEFClient\Actions\ValidateXml\ValidateXmlHandler;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\DTOs\HttpClient\Request;
use N1ebieski\KSEFClient\Exceptions\ExceptionHandler;
use N1ebieski\KSEFClient\Exceptions\XmlValidationException;
use N1ebieski\KSEFClient\Factories\CertificateFactory;
use N1ebieski\KSEFClient\Requests\AbstractHandler;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\Method;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\Uri;
use N1ebieski\KSEFClient\ValueObjects\SchemaPath;

final class XadesSignatureHandler extends AbstractHandler
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly SignDocumentHandler $signDocument,
        private readonly ValidateXmlHandler $validateXml,
        private readonly ExceptionHandler $exceptionHandler,
        private readonly Config $config
    ) {
    }

    public function handle(XadesSignatureRequest | XadesSignatureXmlRequest $request): ResponseInterface
    {
        $signedXml = $request->toXml();

        if ($this->config->validateXml) {
            try {
                $this->validateXml->handle(new ValidateXmlAction(
                    document: $signedXml,
                    schemaPath: SchemaPath::from(Utility::basePath('resources/xsd/authv2.xsd'))
                ));
            } catch (XmlValidationException $exception) {
                $this->exceptionHandler->handle($exception);
            }
        }

        if ($request instanceof XadesSignatureRequest) {
            $signedXml = $this->signDocument->handle(
                new SignDocumentAction(
                    certificate: CertificateFactory::make($request->certificatePath),
                    document: $request->toXml(),
                )
            );
        }

        return $this->client
            ->withoutAccessToken()
            ->sendRequest(new Request(
                method: Method::Post,
                uri: Uri::from('auth/xades-signature'),
                headers: [
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/json',
                ],
                parameters: $request->toParameters(),
                body: $signedXml
            ));
    }
}
