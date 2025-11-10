<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Tests\Feature;

use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\EncryptionKey;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public function createClient(
        ?string $identifier = null,
        ?string $certificatePath = null,
        ?string $certificatePassphrase = null,
        ?EncryptionKey $encryptionKey = null
    ): ClientResourceInterface {
        /** @var array<string, string> $_ENV */
        $client = (new ClientBuilder())
            ->withMode(Mode::Test)
            ->withIdentifier($identifier ?? $_ENV['NIP_1'])
            ->withCertificatePath(
                Utility::basePath($certificatePath ?? $_ENV['CERTIFICATE_PATH_1']),
                $certificatePassphrase ?? $_ENV['CERTIFICATE_PASSPHRASE_1']
            );

        if ($encryptionKey instanceof EncryptionKey) {
            $client = $client->withEncryptionKey($encryptionKey);
        }

        return $client->build();
    }

    public function revokeKsefToken(string $referenceNumber): void
    {
        $client = $this->createClient();

        $response = $client->tokens()->revoke([
            'referenceNumber' => $referenceNumber
        ])->status();

        expect($response)->toBe(204);
    }

    public function revokeCurrentSession(ClientResourceInterface $client): void
    {
        $response = $client->auth()->sessions()->revokeCurrent()->status();

        expect($response)->toBe(204);
    }
}
