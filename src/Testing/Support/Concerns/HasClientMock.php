<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Support\Concerns;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Exception\ExceptionHandler;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\HttpClient\Response;
use N1ebieski\KSEFClient\HttpClient\ValueObjects\BaseUri;
use N1ebieski\KSEFClient\Resources\ClientResource;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractResponseFixture;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

trait HasClientMock
{
    public function getClientStub(AbstractResponseFixture $response): ClientResourceInterface
    {
        /** @var TestCase $this */
        //@phpstan-ignore-next-line
        $streamStub = $this->createStub(StreamInterface::class);
        $streamStub->method('getContents')->willReturn($response->toContents());

        $responseStub = $this->createStub(ResponseInterface::class);
        $responseStub->method('getStatusCode')->willReturn($response->statusCode);
        $responseStub->method('getBody')->willReturn($streamStub);

        $httpClientStub = $this->createStub(HttpClientInterface::class);
        $httpClientStub->method('sendRequest')->willReturn(new Response($responseStub, new ExceptionHandler()));

        return new ClientResource($httpClientStub, new Config(
            baseUri: new BaseUri(Mode::Test->getApiUrl()->value),
            encryptionKey: EncryptionKeyFactory::makeRandom()
        ));
    }
}
