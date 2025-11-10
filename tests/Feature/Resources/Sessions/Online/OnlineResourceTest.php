<?php

use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesAction;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesHandler;
use N1ebieski\KSEFClient\DTOs\QRCodes;
use N1ebieski\KSEFClient\DTOs\Requests\Sessions\Faktura;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendRequestFixture;
use N1ebieski\KSEFClient\Tests\Feature\AbstractTestCase;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\QRCode;
use N1ebieski\KSEFClient\ValueObjects\Requests\KsefNumber;

/** @var AbstractTestCase $this */

test('send an invoice, check for UPO and generate QR code', function (): void {
    /** @var AbstractTestCase $this */
    /** @var array<string, string> $_ENV */

    $encryptionKey = EncryptionKeyFactory::makeRandom();

    $client = $this->createClient(encryptionKey: $encryptionKey);

    /** @var object{referenceNumber: string} $openResponse */
    $openResponse = $client->sessions()->online()->open([
        'formCode' => 'FA (3)',
    ])->object();

    $fakturaFixture = (new FakturaSprzedazyTowaruFixture())
        ->withNip($_ENV['NIP_1'])
        ->withTodayDate()
        ->withRandomInvoiceNumber();

    $fixture = (new SendRequestFixture())->withFakturaFixture($fakturaFixture);

    /** @var object{referenceNumber: string} $sendResponse */
    $sendResponse = $client->sessions()->online()->send([
        ...$fixture->data,
        'referenceNumber' => $openResponse->referenceNumber,
    ])->object();

    $client->sessions()->online()->close([
        'referenceNumber' => $openResponse->referenceNumber
    ]);

    /** @var object{status: object{code: int}, referenceNumber: string, upoDownloadUrl: string, ksefNumber: string} $statusResponse */
    $statusResponse = Utility::retry(function (int $attempts) use ($client, $openResponse, $sendResponse) {
        /** @var object{status: object{code: int}, referenceNumber: string, upoDownloadUrl: string} $statusResponse */
        $statusResponse = $client->sessions()->invoices()->status([
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

    expect($statusResponse)->toHaveProperty('upoDownloadUrl');
    expect($statusResponse->upoDownloadUrl)->toBeString();

    expect($statusResponse)->toHaveProperty('ksefNumber');
    expect($statusResponse->ksefNumber)->toBeString();

    $faktura = Faktura::from($fakturaFixture->data);

    $generateQRCodesHandler = new GenerateQRCodesHandler(
        qrCodeBuilder: (new QrCodeBuilder())
            ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
            ->labelFont(new OpenSans(size: 12)),
        convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
    );

    $ksefNumber = KsefNumber::from($statusResponse->ksefNumber);

    /** @var QRCodes $qrCodes */
    $qrCodes = $generateQRCodesHandler->handle(new GenerateQRCodesAction(
        nip: $faktura->podmiot1->daneIdentyfikacyjne->nip,
        invoiceCreatedAt: $faktura->fa->p_1->value,
        document: $faktura->toXml(),
        mode: Mode::Test,
        ksefNumber: $ksefNumber
    ));

    expect($qrCodes)
        ->toBeInstanceOf(QRCodes::class)
        ->toHaveProperty('code1');

    expect($qrCodes->code1)
        ->toBeInstanceOf(QRCode::class)
        ->toHaveProperty('raw');

    expect($qrCodes->code1->raw)->toBeString();

    $this->revokeCurrentSession($client);
});
