<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Tests\Requests\Sessions\Invoices\KsefUpo;

use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
use N1ebieski\KSEFClient\Testing\AbstractTestCase;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\KsefUpo\KsefUpoResponseFixture;
use PHPUnit\Framework\Attributes\DataProvider;

final class KsefUpoHandlerTest extends AbstractTestCase
{
    /**
     * @return array<string, array{KsefUpoRequestFixture, KsefUpoResponseFixture}>
     */
    public static function validResponseProvider(): array
    {
        $requests = [
            new KsefUpoRequestFixture(),
        ];

        $responses = [
            new KsefUpoResponseFixture(),
        ];

        $combinations = [];

        foreach ($requests as $request) {
            foreach ($responses as $response) {
                $combinations["{$request->name}, {$response->name}"] = [$request, $response];
            }
        }

        /** @var array<string, array{KsefUpoRequestFixture, KsefUpoResponseFixture}> */
        return $combinations;
    }

    #[DataProvider('validResponseProvider')]
    public function testValidResponse(KsefUpoRequestFixture $requestFixture, KsefUpoResponseFixture $responseFixture): void
    {
        $clientStub = $this->getClientStub($responseFixture);

        $request = KsefUpoRequest::from($requestFixture->data);

        $this->assertFixture($requestFixture->data, $request);

        $response = $clientStub->sessions()->invoices()->ksefUpo($requestFixture->data)->body();

        $this->assertEquals($responseFixture->data, $response);
    }

    public function testInvalidResponse(): void
    {
        $requestFixture = new KsefUpoRequestFixture();
        $responseFixture = new ErrorResponseFixture();

        $this->assertExceptionFixture($responseFixture->data);

        $clientStub = $this->getClientStub($responseFixture);

        $clientStub->sessions()->invoices()->ksefUpo($requestFixture->data);
    }
}
