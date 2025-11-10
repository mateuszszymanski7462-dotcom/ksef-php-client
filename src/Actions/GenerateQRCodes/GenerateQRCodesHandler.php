<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Actions\GenerateQRCodes;

use Endroid\QrCode\Builder\BuilderInterface as QrCodeBuilderInterface;
use N1ebieski\KSEFClient\Actions\AbstractHandler;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawAction;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\DTOs\QRCodes;
use N1ebieski\KSEFClient\DTOs\Requests\Auth\ContextIdentifierGroup;
use N1ebieski\KSEFClient\Support\Str;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\CertificateSerialNumber;
use N1ebieski\KSEFClient\ValueObjects\PrivateKeyType;
use N1ebieski\KSEFClient\ValueObjects\QRCode;
use OpenSSLAsymmetricKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use RuntimeException;

final class GenerateQRCodesHandler extends AbstractHandler
{
    public function __construct(
        private readonly QrCodeBuilderInterface $qrCodeBuilder,
        private readonly ConvertEcdsaDerToRawHandler $convertEcdsaDerToRawHandler
    ) {
    }

    public function handle(GenerateQRCodesAction $action): QRCodes
    {
        $invoiceBase64 = Str::base64URLEncode(hash('sha256', $action->document, true));

        $code1Parts = [
            (string) $action->mode->getClientAppInvoiceUrl(),
            (string) $action->nip,
            $action->invoiceCreatedAt->format('d-m-Y'),
            $invoiceBase64
        ];

        $invoiceLink = implode('/', $code1Parts);

        $raw1 = $this->qrCodeBuilder
            ->data($invoiceLink)
            ->labelText($action->ksefNumber->value ?? 'OFFLINE')
            ->build()
            ->getString();

        $code1 = QRCode::from($raw1, $invoiceLink);

        $code2 = null;

        if (
            $action->certificate instanceof Certificate
            && $action->certificateSerialNumber instanceof CertificateSerialNumber
            && $action->contextIdentifierGroup instanceof ContextIdentifierGroup
        ) {
            $code2Parts = [
                (string) $action->mode->getClientAppCertificateUrl(),
                $action->contextIdentifierGroup->identifierGroup->getIdentifier()->getType(),
                (string) $action->contextIdentifierGroup->identifierGroup->getIdentifier(),
                (string) $action->nip,
                (string) $action->certificateSerialNumber,
                $invoiceBase64
            ];

            $certificateLink = implode('/', $code2Parts);

            /** @var string $certificateLinkToSign */
            $certificateLinkToSign = preg_replace('#^https://#', '', rtrim($certificateLink, '/'));

            $signature = match ($action->certificate->getPrivateKeyType()) {
                PrivateKeyType::RSA => $this->handleSignatureByRSAPrivateKey(
                    $certificateLinkToSign,
                    $action->certificate->privateKey
                ),
                PrivateKeyType::EC => $this->handleSignatureByECPrivateKey(
                    $certificateLinkToSign,
                    $action->certificate->privateKey
                ),
            };

            $signatureBase64 = Str::base64URLEncode($signature);

            $certificateLink .= "/{$signatureBase64}";

            $raw2 = $this->qrCodeBuilder
                ->data($certificateLink)
                ->labelText('CERTYFIKAT')
                ->build()
                ->getString();

            $code2 = QRCode::from($raw2, $certificateLink);
        }

        return new QRCodes($code1, $code2);
    }

    private function handleSignatureByRSAPrivateKey(string $data, OpenSSLAsymmetricKey $privateKey): string
    {
        $privateKeyAsString = '';

        $result = openssl_pkey_export($privateKey, $privateKeyAsString);

        if ($result === false) {
            throw new RuntimeException('Unable to export private key');
        }

        /** @var PrivateKey $private */
        //@phpstan-ignore-next-line
        $private = PublicKeyLoader::loadPrivateKey($privateKeyAsString);

        //@phpstan-ignore-next-line
        return $private->withPadding(RSA::SIGNATURE_PSS)
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->withSaltLength(32)
            ->sign($data);
    }

    private function handleSignatureByECPrivateKey(string $data, OpenSSLAsymmetricKey $privateKey): string
    {
        $signature = '';

        $sign = openssl_sign(
            $data,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if ($sign === false) {
            throw new RuntimeException('Unable to sign link');
        }

        return $this->convertEcdsaDerToRawHandler->handle(
            new ConvertEcdsaDerToRawAction($signature, 32) //@phpstan-ignore-line
        );
    }
}
