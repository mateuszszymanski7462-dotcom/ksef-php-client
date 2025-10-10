<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Requests\Sessions\Online\Send\SendRequest;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaKorygujacaDaneNabywcyFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaKorygujacaUniwersalnaFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyUslugLeasinguOperacyjnegoFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaUproszczonaFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaVatMarzaFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaWWalucieObcejFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaZaliczkowaZDodatkowymNabywcaFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaZZalacznikiemFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendResponseFixture;

use function N1ebieski\KSEFClient\Tests\getClientStub;

/**
 * @return array<string, array{SendRequestFixture, SendResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $requests = [
        (new SendRequestFixture())->withFaktura(new FakturaSprzedazyTowaruFixture())->withName('faktura sprzedaży towaru'),
        (new SendRequestFixture())->withFaktura(new FakturaKorygujacaDaneNabywcyFixture())->withName('faktura korygująca dane nabywcy'),
        (new SendRequestFixture())->withFaktura(new FakturaKorygujacaUniwersalnaFixture())->withName('faktura korygująca uniwersalna'),
        (new SendRequestFixture())->withFaktura(new FakturaSprzedazyUslugLeasinguOperacyjnegoFixture())->withName('faktura sprzedaży usług leasingu operacyjnego'),
        (new SendRequestFixture())->withFaktura(new FakturaZaliczkowaZDodatkowymNabywcaFixture())->withName('faktura zaliczkowa z dodatkowym nabywcą'),
        (new SendRequestFixture())->withFaktura(new FakturaUproszczonaFixture())->withName('faktura uproszczona'),
        (new SendRequestFixture())->withFaktura(new FakturaVatMarzaFixture())->withName('faktura VAT marża'),
        (new SendRequestFixture())->withFaktura(new FakturaWWalucieObcejFixture())->withName('faktura w walucie obcej'),
        (new SendRequestFixture())->withFaktura(new FakturaZZalacznikiemFixture())->withName('faktura z załącznikiem'),
    ];

    $responses = [
        new SendResponseFixture(),
    ];

    $combinations = [];

    foreach ($requests as $request) {
        foreach ($responses as $response) {
            $combinations["{$request->name}, {$response->name}"] = [$request, $response];
        }
    }

    /** @var array<string, array{SendRequestFixture, SendResponseFixture}> */
    return $combinations;
});

test('valid response', function (SendRequestFixture $requestFixture, SendResponseFixture $responseFixture): void {
    $clientStub = getClientStub($responseFixture);

    $request = SendRequest::from($requestFixture->data);

    expect($request)->toBeFixture($requestFixture->data);

    $response = $clientStub->sessions()->online()->send($requestFixture->data)->object();

    expect($response)->toBeFixture($responseFixture->data);
})->with('validResponseProvider');

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        /** @var SendRequestFixture $requestFixture */
        $requestFixture = (new SendRequestFixture())
            ->withFaktura(new FakturaSprzedazyTowaruFixture())->withName('faktura sprzedaży towaru');

        $clientStub = getClientStub($responseFixture);

        $clientStub->sessions()->online()->send($requestFixture->data);
    })->toBeExceptionFixture($responseFixture->data);
});
