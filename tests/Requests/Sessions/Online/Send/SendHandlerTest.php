<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Tests\Requests\Sessions\Online\Send;

use N1ebieski\KSEFClient\Requests\Sessions\Online\Send\SendRequest;
use N1ebieski\KSEFClient\Testing\AbstractTestCase;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\AbstractSendRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaKorygujacaDaneNabywcyRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaKorygujacaUniwersalnaRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaSprzedazyTowaruRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaSprzedazyUslugLeasinguOperacyjnegoRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaUproszczonaRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaVatMarzaRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaWWalucieObcejRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaZaliczkowaZDodatkowymNabywcaRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaZZalacznikiemRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendResponseFixture;
use PHPUnit\Framework\Attributes\DataProvider;

final class SendHandlerTest extends AbstractTestCase
{
    /**
     * @return array<string, array{AbstractSendRequestFixture, SendResponseFixture}>
     */
    public static function validResponseProvider(): array
    {
        $requests = [
            new SendFakturaSprzedazyTowaruRequestFixture()->withName('faktura sprzedaży towaru'),
            new SendFakturaKorygujacaUniwersalnaRequestFixture()->withName('faktura korygująca uniwersalna'),
            new SendFakturaKorygujacaDaneNabywcyRequestFixture()->withName('faktura korygująca dane nabywcy'),
            new SendFakturaSprzedazyUslugLeasinguOperacyjnegoRequestFixture()->withName('faktura sprzedaży usług leasingu operacyjnego'),
            new SendFakturaZaliczkowaZDodatkowymNabywcaRequestFixture()->withName('faktura zaliczkowa z dodatkowym nabywcą'),
            new SendFakturaUproszczonaRequestFixture()->withName('faktura uproszczona'),
            new SendFakturaVatMarzaRequestFixture()->withName('faktura VAT marża'),
            new SendFakturaWWalucieObcejRequestFixture()->withName('faktura w walucie obcej'),
            new SendFakturaZZalacznikiemRequestFixture()->withName('faktura z załącznikiem'),
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

        /** @var array<string, array{AbstractSendRequestFixture, SendResponseFixture}> */
        return $combinations;
    }

    #[DataProvider('validResponseProvider')]
    public function testValidResponse(AbstractSendRequestFixture $requestFixture, SendResponseFixture $responseFixture): void
    {
        $clientStub = $this->getClientStub($responseFixture);

        $request = SendRequest::from($requestFixture->data);

        $this->assertFixture($requestFixture->data, $request);

        $response = $clientStub->sessions()->online()->send($requestFixture->data)->object();

        $this->assertFixture($responseFixture->data, $response);
    }

    public function testInvalidResponse(): void
    {
        $requestFixture = new SendFakturaSprzedazyTowaruRequestFixture();
        $responseFixture = new ErrorResponseFixture();

        $this->assertExceptionFixture($responseFixture->data);

        $clientStub = $this->getClientStub($responseFixture);

        $clientStub->sessions()->online()->send($requestFixture->data);
    }
}
