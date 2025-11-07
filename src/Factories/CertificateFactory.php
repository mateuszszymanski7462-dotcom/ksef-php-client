<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Factories;

use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\CertificatePath;
use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * Special thanks to grafinet/xades-tools > https://github.com/grafinet/xades-tools
 * I could not use their dependency directly, but most of the logic in this class
 * is their authorship
 */
final class CertificateFactory extends AbstractFactory
{
    public static function make(CertificatePath $certificatePath): Certificate
    {
        $pkcs12 = file_get_contents($certificatePath->path);

        if ($pkcs12 === false) {
            throw new RuntimeException('Unable to read the cert file');
        }

        $pkcs12read = openssl_pkcs12_read($pkcs12, $data, $certificatePath->passphrase ?? '');

        if ($pkcs12read === false) {
            throw new RuntimeException(
                sprintf('Unable to read the cert file. OpenSSL: %s', (openssl_error_string() ?: ''))
            );
        }

        /** @var array{pkey: string, cert: string} $data */

        return self::makeFromString($data['cert'], $data['pkey'], $certificatePath->passphrase);
    }

    public static function makeFromString(string $certificate, OpenSSLAsymmetricKey | string $privateKey, ?string $passphrase = null): Certificate
    {
        if ( ! $privateKey instanceof OpenSSLAsymmetricKey) {
            $privateKey = openssl_pkey_get_private($privateKey, $passphrase);
        }

        if ($privateKey === false) {
            throw new RuntimeException(
                sprintf('Unable to read the cert file. OpenSSL: %s', (openssl_error_string() ?: ''))
            );
        }

        /** @var array{issuer: array<string, string>, serialNumberHex: string}|false $info */
        $info = openssl_x509_parse($certificate);

        if ($info === false) {
            throw new RuntimeException(
                sprintf('Unable to read the cert file. OpenSSL: %s', (openssl_error_string() ?: ''))
            );
        }

        return new Certificate($certificate, $info, $privateKey);
    }
}
