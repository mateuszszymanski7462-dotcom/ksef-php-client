<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Tests\Requests\Sessions\Invoices\Upo;

use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo\UpoRequest;
use N1ebieski\KSEFClient\Testing\AbstractTestCase;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\Upo\UpoRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\Upo\UpoResponseFixture;
use PHPUnit\Framework\Attributes\DataProvider;

final class UpoHandlerTest extends AbstractTestCase
{
    /**
     * @return array<string, array{UpoRequestFixture, UpoResponseFixture}>
     */
    public static function validResponseProvider(): array
    {
        $requests = [
            new UpoRequestFixture(),
        ];

        $responses = [
            new UpoResponseFixture(),
        ];

        $combinations = [];

        foreach ($requests as $request) {
            foreach ($responses as $response) {
                $combinations["{$request->name}, {$response->name}"] = [$request, $response];
            }
        }

        /** @var array<string, array{UpoRequestFixture, UpoResponseFixture}> */
        return $combinations;
    }

    #[DataProvider('validResponseProvider')]
    public function testValidResponse(UpoRequestFixture $requestFixture, UpoResponseFixture $responseFixture): void
    {
        $clientStub = $this->getClientStub($responseFixture);

        $request = UpoRequest::from($requestFixture->data);

        $this->assertFixture($requestFixture->data, $request);

        $response = $clientStub->sessions()->invoices()->upo($requestFixture->data)->body();

        $this->assertEquals($responseFixture->data, $response);
    }

    public function testInvalidResponse(): void
    {
        $requestFixture = new UpoRequestFixture();
        $responseFixture = new ErrorResponseFixture();

        $this->assertExceptionFixture($responseFixture->data);

        $clientStub = $this->getClientStub($responseFixture);

        $clientStub->sessions()->invoices()->upo($requestFixture->data);
    }
}
