<?php

use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Exceptions\HttpClient\BadRequestException;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendRequestFixture;
use N1ebieski\KSEFClient\Tests\Feature\AbstractTestCase;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\Requests\Testdata\Subject\SubjectType;

/** @var AbstractTestCase $this */

beforeEach(function (): void {
    $client = (new ClientBuilder())
        ->withMode(Mode::Test)
        ->build();

    try {
        $client->testdata()->subject()->create([
            'subjectNip' => $_ENV['NIP_2'],
            'subjectType' => SubjectType::EnforcementAuthority,
            'description' => 'Subject who gives InvoiceWrite permission',
        ])->status();
    } catch (BadRequestException $exception) {
        if (str_starts_with($exception->getMessage(), '30001')) {
            // ignore
        }
    }
});

afterAll(function (): void {
    $client = (new ClientBuilder())
        ->withMode(Mode::Test)
        ->build();

    $client->testdata()->subject()->remove([
        'nip' => $_ENV['NIP_2'],
    ]);
});

test('give InvoiceWrite permission and send invoice as NIP_2', function (): void {
    /** @var AbstractTestCase $this */
    /** @var array<string, string> $_ENV */

    $clientNip2 = $this->createClient(
        identifier: $_ENV['NIP_2'],
        certificatePath: $_ENV['CERTIFICATE_PATH_2'],
        certificatePassphrase: $_ENV['CERTIFICATE_PASSPHRASE_2']
    );

    $grantsResponse = $clientNip2->permissions()->entities()->grants([
        'subjectIdentifierGroup' => [
            'nip' => $_ENV['NIP_1']
        ],
        'permissions' => [
            [
                'type' => 'InvoiceWrite'
            ]
        ],
        'description' => 'Give InvoiceWrite permission to NIP_1'
    ])->object();

    Utility::retry(function (int $attempts) use ($clientNip2, $grantsResponse) {
        $statusResponse = $clientNip2->permissions()->operations()->status([
            'referenceNumber' => $grantsResponse->referenceNumber,
        ])->object();

        try {
            expect($statusResponse->status->code)->toBe(200);

            return $statusResponse;
        } catch (Throwable $exception) {
            if ($attempts > 2) {
                throw $exception;
            }
        }
    });

    $encryptionKey = EncryptionKeyFactory::makeRandom();

    $clientNip1 = $this->createClient(
        identifier: $_ENV['NIP_2'],
        encryptionKey: $encryptionKey
    );

    $openResponse = $clientNip1->sessions()->online()->open([
        'formCode' => 'FA (3)',
    ])->object();

    $fakturaFixture = (new FakturaSprzedazyTowaruFixture())
        ->withNip($_ENV['NIP_2'])
        ->withTodayDate()
        ->withRandomInvoiceNumber();

    $fixture = (new SendRequestFixture())->withFakturaFixture($fakturaFixture);

    $sendResponse = $clientNip1->sessions()->online()->send([
        ...$fixture->data,
        'referenceNumber' => $openResponse->referenceNumber,
    ])->object();

    $clientNip1->sessions()->online()->close([
        'referenceNumber' => $openResponse->referenceNumber
    ]);

    Utility::retry(function (int $attempts) use ($clientNip1, $openResponse, $sendResponse) {
        $statusResponse = $clientNip1->sessions()->invoices()->status([
            'referenceNumber' => $openResponse->referenceNumber,
            'invoiceReferenceNumber' => $sendResponse->referenceNumber
        ])->object();

        try {
            expect($statusResponse->status->code)->toBe(200);

            return $statusResponse;
        } catch (Throwable $exception) {
            if ($attempts > 2) {
                throw $exception;
            }
        }
    });

    $queryResponse = $clientNip1->permissions()->query()->personal()->grants([
        'contextIdentifierGroup' => [
            'nip' => $_ENV['NIP_2']
        ],
    ])->object();

    expect($queryResponse)->toHaveProperty('permissions');

    expect($queryResponse->permissions)->toBeArray()->not->toBeEmpty();

    expect($queryResponse->permissions[0])->toHaveProperty('id');

    expect($queryResponse->permissions[0]->id)->toBeString();

    $revokePermissionResponse = $clientNip2->permissions()->common()->revoke([
        'permissionId' => $queryResponse->permissions[0]->id
    ])->object();

    Utility::retry(function (int $attempts) use ($clientNip2, $revokePermissionResponse) {
        $statusResponse = $clientNip2->permissions()->operations()->status([
            'referenceNumber' => $revokePermissionResponse->referenceNumber,
        ])->object();

        try {
            expect($statusResponse->status->code)->toBe(200);

            return $statusResponse;
        } catch (Throwable $exception) {
            if ($attempts > 2) {
                throw $exception;
            }
        }
    });

    $this->revokeCurrentSession($clientNip1);
    $this->revokeCurrentSession($clientNip2);
});
