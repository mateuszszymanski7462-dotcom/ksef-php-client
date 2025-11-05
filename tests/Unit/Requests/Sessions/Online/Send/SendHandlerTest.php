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
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaZVatUEFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaZZalacznikiemFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendResponseFixture;
use N1ebieski\KSEFClient\Tests\Unit\AbstractTestCase;

/** @var AbstractTestCase $this */

/**
 * @return array<string, array{SendRequestFixture, SendResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $requests = [
        (new SendRequestFixture())->withFakturaFixture(new FakturaSprzedazyTowaruFixture())->withName('faktura sprzedaży towaru'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaKorygujacaDaneNabywcyFixture())->withName('faktura korygująca dane nabywcy'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaKorygujacaUniwersalnaFixture())->withName('faktura korygująca uniwersalna'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaSprzedazyUslugLeasinguOperacyjnegoFixture())->withName('faktura sprzedaży usług leasingu operacyjnego'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaZaliczkowaZDodatkowymNabywcaFixture())->withName('faktura zaliczkowa z dodatkowym nabywcą'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaUproszczonaFixture())->withName('faktura uproszczona'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaVatMarzaFixture())->withName('faktura VAT marża'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaWWalucieObcejFixture())->withName('faktura w walucie obcej'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaZZalacznikiemFixture())->withName('faktura z załącznikiem'),
        (new SendRequestFixture())->withFakturaFixture(new FakturaZVatUEFixture())->withName('faktura z VAT UE'),
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
    /** @var AbstractTestCase $this */
    $clientStub = $this->createClientStub($responseFixture);

    $request = SendRequest::from($requestFixture->data);

    expect($request)->toBeFixture($requestFixture->data);

    $response = $clientStub->sessions()->online()->send($requestFixture->data)->object();

    expect($response)->toBeFixture($responseFixture->data);
})->with('validResponseProvider')->only();

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        /** @var AbstractTestCase $this */
        /** @var SendRequestFixture $requestFixture */
        $requestFixture = (new SendRequestFixture())
            ->withFakturaFixture(new FakturaSprzedazyTowaruFixture())->withName('faktura sprzedaży towaru');

        $clientStub = $this->createClientStub($responseFixture);

        $clientStub->sessions()->online()->send($requestFixture->data);
    })->toBeExceptionFixture($responseFixture->data);
});
