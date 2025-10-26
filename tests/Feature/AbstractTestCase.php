<?php

namespace N1ebieski\KSEFClient\Tests\Feature;

use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public function createClient(): ClientResourceInterface
    {
        /** @var array<string, string> $_ENV */
        return (new ClientBuilder())
            ->withMode(Mode::Test)
            ->withIdentifier($_ENV['NIP'])
            ->withCertificatePath(Utility::basePath($_ENV['CERTIFICATE_PATH']), $_ENV['CERTIFICATE_PASSPHRASE'])
            ->build();
    }

    public function revokeKsefToken(string $referenceNumber): void
    {
        $client = $this->createClient();

        $response = $client->tokens()->revoke([
            'referenceNumber' => $referenceNumber
        ])->status();

        expect($response)->toBe(204);
    }

    public function revokeCurrentSession(): void
    {
        $client = $this->createClient();

        $response = $client->auth()->sessions()->revokeCurrent()->status();

        expect($response)->toBe(204);
    }
}
