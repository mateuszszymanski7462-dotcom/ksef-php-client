<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaAbstractFixture;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Exceptions\ExceptionHandler;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\HttpClient\Response;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendRequest;
use N1ebieski\KSEFClient\Resources\ClientResource;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Batch\OpenAndSend\OpenAndSendRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Batch\OpenAndSend\OpenAndSendResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Batch\OpenAndSend\SendResponseFixture;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\BaseUri;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;

use function N1ebieski\KSEFClient\Tests\getClientStub;
use function N1ebieski\KSEFClient\Tests\getHttpClientStub;
use function N1ebieski\KSEFClient\Tests\getResponseStub;

/**
 * @return array<string, array{OpenAndSendRequestFixture, OpenAndSendResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $requests = [
        (new OpenAndSendRequestFixture())->withFakturaFixtures(array_map(
            fn (): FakturaAbstractFixture => (new FakturaSprzedazyTowaruFixture())
                ->withTodayDate()
                ->withRandomInvoiceNumber(),
            range(1, 3)
        )),
    ];

    $responses = [
        new OpenAndSendResponseFixture(),
    ];

    $combinations = [];

    foreach ($requests as $request) {
        foreach ($responses as $response) {
            $combinations["{$request->name}, {$response->name}"] = [$request, $response];
        }
    }

    /** @var array<string, array{OpenAndSendRequestFixture, OpenAndSendResponseFixture}> */
    return $combinations;
});

test('valid response', function (OpenAndSendRequestFixture $requestFixture, OpenAndSendResponseFixture $responseFixture): void {
    $encryptedKey = EncryptedKey::from('string', 'string');

    $httpClientStub = getHttpClientStub($responseFixture);
    $httpClientStub->shouldReceive('sendAsyncRequest')
        ->andReturn([new Response(getResponseStub(new SendResponseFixture()), new ExceptionHandler())]);

    $clientStub = (new ClientResource($httpClientStub, new Config(
        baseUri: new BaseUri(Mode::Test->getApiUrl()->value),
        encryptionKey: EncryptionKeyFactory::makeRandom()
    )))->withEncryptedKey($encryptedKey);

    $request = OpenAndSendRequest::from($requestFixture->data);

    expect($request)->toBeFixture($requestFixture->data);

    $response = $clientStub->sessions()->batch()->openAndSend($requestFixture->data)->object();

    expect($response)->toBeFixture($responseFixture->data);
})->with('validResponseProvider');

test('invalid response without EncryptedKey', function (): void {
    $requestFixture = new OpenAndSendRequestFixture();
    $responseFixture = new OpenAndSendResponseFixture();

    $clientStub = getClientStub($responseFixture);

    $clientStub->sessions()->batch()->openAndSend($requestFixture->data)->object();
})->throws(RuntimeException::class, 'Encrypted key is required to open session.');

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        $requestFixture = new OpenAndSendRequestFixture();

        $clientStub = getClientStub($responseFixture);

        $clientStub->sessions()->batch()->openAndSend($requestFixture->data);
    })->toBeExceptionFixture($responseFixture->data);
});
