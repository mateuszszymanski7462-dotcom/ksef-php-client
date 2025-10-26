<?php

use N1ebieski\KSEFClient\Actions\ConvertCertificateToPkcs12\ConvertCertificateToPkcs12Action;
use N1ebieski\KSEFClient\Actions\ConvertCertificateToPkcs12\ConvertCertificateToPkcs12Handler;
use N1ebieski\KSEFClient\Actions\ConvertDerToPem\ConvertDerToPemAction;
use N1ebieski\KSEFClient\Actions\ConvertDerToPem\ConvertDerToPemHandler;
use N1ebieski\KSEFClient\Actions\ConvertPemToDer\ConvertPemToDerAction;
use N1ebieski\KSEFClient\Actions\ConvertPemToDer\ConvertPemToDerHandler;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\DTOs\DN;
use N1ebieski\KSEFClient\Factories\CSRFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Tests\Feature\AbstractTestCase;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\PrivateKeyType;
use N1ebieski\KSEFClient\ValueObjects\RefreshToken;

/** @var AbstractTestCase $this */

/**
 * @return array<string, array<PrivateKeyType>>
 */
dataset('privateKeyTypeProvider', fn (): array => [
    'RSA' => [PrivateKeyType::RSA],
    'EC' => [PrivateKeyType::EC],
]);

test('auto authorization via certificate .p12', function (): void {
    /** @var AbstractTestCase $this */
    $client = $this->createClient();

    $accessToken = $client->getAccessToken();
    $refreshToken = $client->getRefreshToken();

    expect($accessToken)->toBeInstanceOf(AccessToken::class);
    expect($accessToken?->validUntil)->toBeGreaterThan(new DateTimeImmutable());

    expect($refreshToken)->toBeInstanceOf(RefreshToken::class);
    expect($refreshToken?->validUntil)->toBeGreaterThan(new DateTimeImmutable('+6 days'));

    $this->revokeCurrentSession();
});

test('auto authorization via KSEF certificate .p12', function (PrivateKeyType $privateKeyType): void {
    /**
     * @var AbstractTestCase $this
     * @var array<string, string> $_ENV
     */
    $client = $this->createClient();

    $dataResponse = $client->certificates()->enrollments()->data()->json();

    $dn = DN::from($dataResponse);

    $csr = CSRFactory::make($dn, $privateKeyType);

    $csrToDer = (new ConvertPemToDerHandler())->handle(new ConvertPemToDerAction($csr->raw));

    /** @var object{referenceNumber: string} */
    $sendResponse = $client->certificates()->enrollments()->send([
        'certificateName' => 'testing',
        'certificateType' => 'Authentication',
        'csr' => base64_encode($csrToDer),
    ])->object();

    /** @var object{status: object{code: int, description: string}, certificateSerialNumber: string} */
    $statusResponse = Utility::retry(function () use ($client, $sendResponse) {
        /** @var object{status: object{code: int, description: string}, certificateSerialNumber: string} */
        $statusResponse = $client->certificates()->enrollments()->status([
            'referenceNumber' => $sendResponse->referenceNumber
        ])->object();

        if ($statusResponse->status->code === 200) {
            return $statusResponse;
        }

        if ($statusResponse->status->code >= 400) {
            throw new RuntimeException(
                $statusResponse->status->description,
                $statusResponse->status->code
            );
        }
    });

    /** @var object{certificates: array<object{certificate: string}>} */
    $retrieveResponse = $client->certificates()->retrieve([
        'certificateSerialNumbers' => [$statusResponse->certificateSerialNumber]
    ])->object();

    $certificate = base64_decode((string) $retrieveResponse->certificates[0]->certificate);

    $certificateToPem = (new ConvertDerToPemHandler())->handle(
        new ConvertDerToPemAction($certificate, 'CERTIFICATE')
    );

    $certificateToPkcs12 = (new ConvertCertificateToPkcs12Handler())->handle(
        new ConvertCertificateToPkcs12Action(
            certificate: new Certificate($certificateToPem, [], $csr->privateKey), //@phpstan-ignore-line
            passphrase: $_ENV['KSEF_AUTH_CERTIFICATE_PASSPHRASE']
        )
    );

    file_put_contents(Utility::basePath($_ENV['KSEF_AUTH_CERTIFICATE_PATH']), $certificateToPkcs12);

    $client = (new ClientBuilder())
        ->withMode(Mode::Test)
        ->withIdentifier($_ENV['NIP'])
        ->withCertificatePath(Utility::basePath($_ENV['KSEF_AUTH_CERTIFICATE_PATH']), $_ENV['KSEF_AUTH_CERTIFICATE_PASSPHRASE'])
        ->build();

    $accessToken = $client->getAccessToken();
    $refreshToken = $client->getRefreshToken();

    expect($accessToken)->toBeInstanceOf(AccessToken::class);
    expect($accessToken?->validUntil)->toBeGreaterThan(new DateTimeImmutable());

    expect($refreshToken)->toBeInstanceOf(RefreshToken::class);
    expect($refreshToken?->validUntil)->toBeGreaterThan(new DateTimeImmutable('+6 days'));

    $revokeCertificate = $client->certificates()->revoke([
        'certificateSerialNumber' => $statusResponse->certificateSerialNumber
    ])->status();

    expect($revokeCertificate)->toBe(204);

    $this->revokeCurrentSession();
})->with('privateKeyTypeProvider');

test('auto authorization via KSEF Token', function (): void {
    /**
     * @var AbstractTestCase $this
     * @var array<string, string> $_ENV
     */
    $client = $this->createClient();

    /** @var object{token: string, referenceNumber: string} */
    $response = $client->tokens()->create([
        'permissions' => [
            'InvoiceRead',
            'InvoiceWrite'
        ],
        'description' => 'testing',
    ])->object();

    $client = (new ClientBuilder())
        ->withMode(Mode::Test)
        ->withIdentifier($_ENV['NIP'])
        ->withKsefToken($response->token)
        ->build();

    $accessToken = $client->getAccessToken();
    $refreshToken = $client->getRefreshToken();

    expect($accessToken)->toBeInstanceOf(AccessToken::class);
    expect($accessToken?->validUntil)->toBeGreaterThan(new DateTimeImmutable());

    expect($refreshToken)->toBeInstanceOf(RefreshToken::class);
    expect($refreshToken?->validUntil)->toBeGreaterThan(new DateTimeImmutable('+6 days'));

    $this->revokeKsefToken($response->referenceNumber);
    $this->revokeCurrentSession();
});
