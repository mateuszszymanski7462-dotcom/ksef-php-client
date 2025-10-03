<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Tests\Requests\Sessions\Online\Invoices;

use N1ebieski\KSEFClient\Requests\Sessions\Online\Invoices\InvoicesRequest;
use N1ebieski\KSEFClient\Testing\AbstractTestCase;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\AbstractInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaKorygujacaDaneNabywcyInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaKorygujacaUniwersalnaInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaSprzedazyTowaruInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaSprzedazyUslugLeasinguOperacyjnegoInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaUproszczonaInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaVatMarzaInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaWWalucieObcejInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaZaliczkowaZDodatkowymNabywcaInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\FakturaZZalacznikiemInvoicesRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices\InvoicesResponseFixture;
use PHPUnit\Framework\Attributes\DataProvider;

final class InvoicesHandlerTest extends AbstractTestCase
{
    /**
     * @return array<string, array{AbstractInvoicesRequestFixture, InvoicesResponseFixture}>
     */
    public static function validResponseProvider(): array
    {
        $requests = [
            new FakturaSprzedazyTowaruInvoicesRequestFixture()->withName('faktura sprzedaży towaru'),
            new FakturaKorygujacaUniwersalnaInvoicesRequestFixture()->withName('faktura korygująca uniwersalna'),
            new FakturaKorygujacaDaneNabywcyInvoicesRequestFixture()->withName('faktura korygująca dane nabywcy'),
            new FakturaSprzedazyUslugLeasinguOperacyjnegoInvoicesRequestFixture()->withName('faktura sprzedaży usług leasingu operacyjnego'),
            new FakturaZaliczkowaZDodatkowymNabywcaInvoicesRequestFixture()->withName('faktura zaliczkowa z dodatkowym nabywcą'),
            new FakturaUproszczonaInvoicesRequestFixture()->withName('faktura uproszczona'),
            new FakturaVatMarzaInvoicesRequestFixture()->withName('faktura VAT marża'),
            new FakturaWWalucieObcejInvoicesRequestFixture()->withName('faktura w walucie obcej'),
            new FakturaZZalacznikiemInvoicesRequestFixture()->withName('faktura z załącznikiem'),
        ];

        $responses = [
            new InvoicesResponseFixture(),
        ];

        $combinations = [];

        foreach ($requests as $request) {
            foreach ($responses as $response) {
                $combinations["{$request->name}, {$response->name}"] = [$request, $response];
            }
        }

        /** @var array<string, array{AbstractInvoicesRequestFixture, InvoicesResponseFixture}> */
        return $combinations;
    }

    #[DataProvider('validResponseProvider')]
    public function testValidResponse(AbstractInvoicesRequestFixture $requestFixture, InvoicesResponseFixture $responseFixture): void
    {
        $clientStub = $this->getClientStub($responseFixture);

        $request = InvoicesRequest::from($requestFixture->data);

        $this->assertFixture($requestFixture->data, $request);

        $response = $clientStub->sessions()->online()->invoices($requestFixture->data)->object();

        $this->assertFixture($responseFixture->data, $response);
    }

    public function testInvalidResponse(): void
    {
        $requestFixture = new FakturaSprzedazyTowaruInvoicesRequestFixture();
        $responseFixture = new ErrorResponseFixture();

        $this->assertExceptionFixture($responseFixture->data);

        $clientStub = $this->getClientStub($responseFixture);

        $clientStub->sessions()->online()->invoices($requestFixture->data);
    }
}
