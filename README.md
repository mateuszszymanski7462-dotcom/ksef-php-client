
![1920x810](https://github.com/user-attachments/assets/7db28b6a-80fc-4651-9d07-f04aad6ec8c7)

# KSEF PHP Client

> **This package is not production ready yet!**

PHP API client that allows you to interact with the [API Krajowego Systemu e-Faktur](https://www.gov.pl/web/kas/api-krajowego-system-e-faktur)

Main features:

- Support for authorization via qualified certificates, KSEF certificates, KSEF tokens, and trusted signature ePUAP (manual)
- Handling of CSR certification requests
- Automatic access token refresh
- Logical invoice structure mapped to DTOs

|  KSEF Version  | Branch Version |
|:--------------:|:--------------:|
|       2.0      |      main      |
|       1.0      |       1.x      |

## Table of Contents

- [Get Started](#get-started)
    - [Client configuration](#client-configuration)
    - [Auto mapping](#auto-mapping)
- [Authorization](#authorization)
    - [Auto authorization via KSEF Token](#auto-authorization-via-ksef-token)
    - [Auto authorization via certificate .p12](#auto-authorization-via-certificate-p12)
    - [Manual authorization](#manual-authorization)
- [Resources](#resources)
    - [Auth](#auth)
        - [Challenge](#challenge)
        - [Xades Signature](#xades-signature)
        - [Auth Status](#auth-status)
        - [Token](#token)
            - [Redeem](#redeem)
            - [Refresh](#refresh)
    - [Certificates](#certificates)
        - [Limits](#limits)
        - [Enrollments](#enrollments)
            - [Enrollments Data](#enrollments-data)
            - [Enrollments Send](#enrollments-send)
            - [Enrollments Status](#enrollments-status)
        - [Certificates Retrieve](#certificates-retrieve)
        - [Certificates Revoke](#certificates-revoke)
        - [Certificates Query](#certificates-query)
    - [Testdata](#testdata)
        - [Person](#person)
            - [Person Create](#person-create)
            - [Person Remove](#person-remove)
- [Examples](#examples)
    - [Generate a KSEF certificate and save to .p12 file](#generate-a-ksef-certificate-and-save-to-p12-file)
    - [Send an invoice and check for UPO](#send-an-invoice-and-check-for-upo)
    - [Fetch invoices using encryption key](#fetch-invoices-using-encryption-key)
- [Testing](#testing)
- [Roadmap](#roadmap)
- [Special thanks](#special-thanks)

## Get Started

> **Requires [PHP 8.4+](https://www.php.net/releases/)**

First, install `ksef-php-client` via the [Composer](https://getcomposer.org/) package manager:

```bash
composer require n1ebieski/ksef-php-client
```

Ensure that the `php-http/discovery` composer plugin is allowed to run or install a client manually if your project does not already have a PSR-18 client integrated.

```bash
composer require guzzlehttp/guzzle
```

### Client configuration

```php
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;

$client = new ClientBuilder()
    ->withMode(Mode::Production) // Choice between: Test, Demo, Production
    ->withApiUrl($_ENV['KSEF_API_URL']) // Optional, default is set by Mode selection
    ->withHttpClient(new \GuzzleHttp\Client(...)) // Optional PSR-18 implementation, default is set by Psr18ClientDiscovery::find()
    ->withLogger(new \Monolog\Logger(...)) // Optional PSR-3 implementation, default is set by PsrDiscovery\Discover::log()
    ->withLogPath($_ENV['PATH_TO_LOG_FILE'], $_ENV['LOG_LEVEL']) // Optional, level: null disables logging
    ->withAccessToken($_ENV['ACCESS_TOKEN']) // Optional, if present, auto authorization is skipped
    ->withRefreshToken($_ENV['REFRESH_TOKEN']) // Optional, if present, auto refresh access token is enabled
    ->withKsefToken($_ENV['KSEF_TOKEN']) // Required for API Token authorization. Optional otherwise
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE']) // Required .p12 file for Certificate authorization. Optional otherwise
    ->withEncryptionKey(EncryptionKeyFactory::makeRandom()) // Required for invoice resources. Remember to save this value!
    ->withIdentifier('NIP_NUMBER') // Required for authorization. Optional otherwise
    ->build();
```

### Auto mapping

Each resource supports mapping through both an array and a DTO, for example:

```php
use N1ebieski\KSEFClient\Requests\Auth\Status\StatusRequest;
use N1ebieski\KSEFClient\Requests\ValueObjects\ReferenceNumber;

$authorisationStatusResponse = $client->auth()->status(new StatusRequest(
    referenceNumber: ReferenceNumber::from('20250508-EE-B395BBC9CD-A7DB4E6095-BD')
))->object();
```

or:

```php
$authorisationStatusResponse = $client->auth()->status([
    'referenceNumber' => '20250508-EE-B395BBC9CD-A7DB4E6095-BD'
])->object();
```

## Authorization

### Auto authorization via KSEF Token

```php
use N1ebieski\KSEFClient\ClientBuilder;

$client = new ClientBuilder()
    ->withKsefToken($_ENV['KSEF_KEY'])
    ->withIdentifier('NIP_NUMBER')
    ->build();

// Do something with the available resources
```

### Auto authorization via certificate .p12

```php
use N1ebieski\KSEFClient\ClientBuilder;

$client = new ClientBuilder()
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->withIdentifier('NIP_NUMBER')
    ->build();

// Do something with the available resources
```

### Manual authorization

```php
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Requests\Auth\DTOs\XadesSignature;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureXmlRequest;

$client = new ClientBuilder()->build();

$nip = 'NIP_NUMBER';

$authorisationChallengeResponse = $client->auth()->challenge()->object();

$xml = XadesSignature::from([
    'challenge' => $authorisationChallengeResponse->challenge,
    'contextIdentifierGroup' => [
        'contextIdentifierTypeGroup' => [
            'nip' => $nip
        ]
    ],
    'subjectIdentifierType' => 'certificateSubject'
])->toXml();

$signedXml = 'SIGNED_XML_DOCUMENT'; // Sign a xml document via Szafir, ePUAP etc.

$authorisationAccessResponse = $client->auth()->xadesSignature(
    new XadesSignatureXmlRequest($signedXml)
)->object();

$client = $client->withAccessToken($authorisationAccessResponse->authenticationToken->token);

$authorisationStatusResponse = Utility::retry(function () use ($client, $authorisationAccessResponse) {
    $authorisationStatusResponse = $client->auth()->status([
        'referenceNumber' => $authorisationAccessResponse->referenceNumber
    ])->object();

    if ($authorisationStatusResponse->status->code === 200) {
        return $authorisationStatusResponse;
    }

    if ($authorisationStatusResponse->status->code >= 400) {
        throw new RuntimeException(
            $authorisationStatusResponse->status->description,
            $authorisationStatusResponse->status->code
        );
    }
});

$authorisationTokenResponse = $client->auth()->token()->redeem()->object();

$client = $client
    ->withAccessToken(
        token: $authorisationTokenResponse->accessToken->token, 
        validUntil: $authorisationTokenResponse->accessToken->validUntil
    )
    ->withRefreshToken(
        token: $authorisationTokenResponse->refreshToken->token,
        validUntil: $authorisationTokenResponse->refreshToken->validUntil
    );

// Do something with the available resources
```

## Resources

### Auth

#### Challenge

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1challenge/post

```php
$response = $client->auth()->challenge()->object();
```

#### Xades Signature

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1xades-signature/post

```php
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureRequest;

$response = $client->auth()->xadesSignature(
    new XadesSignatureRequest(...)
)->object();
```

or:

```php
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureXmlRequest;

$response = $client->auth()->xadesSignature(
    new XadesSignatureXmlRequest(...)
)->object();
```

#### Auth Status

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1%7BreferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Auth\Status\StatusRequest;

$response = $client->auth()->status(
    new StatusRequest(...)
)->object();
```

#### Token

##### Redeem

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1token~1redeem/post

```php
$response = $client->auth()->token()->redeem()->object();
```

##### Refresh

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1token~1refresh/post

```php
$response = $client->auth()->token()->refresh()->object();
```

### Certificates

#### Limits

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1limits/get

```php
$response = $client->certificates()->limits()->object();
```

#### Enrollments

##### Enrollments Data

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1enrollments~1data/get

```php
$response = $client->certificates()->enrollments()->data()->object();
```

##### Enrollments Send

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1enrollments/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Send\SendRequest;

$response = $client->certificates()->enrollments()->send(
    new SendRequest(...)
)->object();
```

##### Enrollments Status

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1enrollments~1%7BreferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Status\StatusRequest;

$response = $client->certificates()->enrollments()->status(
    new StatusRequest(...)
)->object();
```

#### Certificates Retrieve

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1retrieve/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Retrieve\RetrieveRequest;

$response = $client->certificates()->retrieve(
    new RetrieveRequest(...)
)->object();
```

#### Certificates Revoke

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1%7BcertificateSerialNumber%7D~1revoke/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Revoke\RevokeRequest;

$response = $client->certificates()->revoke(
    new RevokeRequest(...)
)->status();
```

#### Certificates Query

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1query/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Query\QueryRequest;

$response = $client->certificates()->query(
    new QueryRequest(...)
)->object();
```

### Testdata

#### Person

##### Person Create

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Dane-testowe/paths/~1api~1v2~1testdata~1person/post

```php
use N1ebieski\KSEFClient\Requests\Testdata\Person\Create\CreateRequest;

$response = $client->testdata()->person()->create(
    new CreateRequest(...)
)->status();
```

##### Person Remove

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Dane-testowe/paths/~1api~1v2~1testdata~1person~1remove/post

```php
use N1ebieski\KSEFClient\Requests\Testdata\Person\Remove\RemoveRequest;

$response = $client->testdata()->person()->remove(
    new RemoveRequest(...)
)->status();
```

## Examples

### Generate a KSEF certificate and save to .p12 file

```php
use N1ebieski\KSEFClient\Actions\ConvertDerToPem\ConvertDerToPemAction;
use N1ebieski\KSEFClient\Actions\ConvertDerToPem\ConvertDerToPemHandler;
use N1ebieski\KSEFClient\Actions\ConvertPemToDer\ConvertPemToDerAction;
use N1ebieski\KSEFClient\Actions\ConvertPemToDer\ConvertPemToDerHandler;
use N1ebieski\KSEFClient\Actions\SaveCertificateToPkcs12\SaveCertificateToPkcs12Action;
use N1ebieski\KSEFClient\Actions\SaveCertificateToPkcs12\SaveCertificateToPkcs12Handler;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\DTOs\DN;
use N1ebieski\KSEFClient\Factories\CSRFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\PrivateKeyType;

$client = new ClientBuilder()
    ->withIdentifier('NIP_NUMBER')
    // To generate the KSEF certificate, you must authorize the qualified certificate the first time
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->build();

$dataResponse = $client->certificates()->enrollments()->data()->json();

$dn = DN::from($dataResponse);

// You can choose beetween EC or RSA private key type
$csr = CSRFactory::make($dn, PrivateKeyType::EC);

$csrToDer = new ConvertPemToDerHandler()->handle(new ConvertPemToDerAction($csr->raw));

$sendResponse = $client->certificates()->enrollments()->send([
    'certificateName' => 'My first certificate',
    'certificateType' => 'Authentication',
    'csr' => base64_encode($csrToDer),
])->object();

$statusResponse = Utility::retry(function () use ($client, $sendResponse) {
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

$retrieveResponse = $client->certificates()->retrieve([
    'certificateSerialNumbers' => [$statusResponse->certificateSerialNumber]
])->object();

$certificate = base64_decode($retrieveResponse->certificates[0]->certificate);

$certificateToPem = new ConvertDerToPemHandler()->handle(
    new ConvertDerToPemAction($certificate, 'CERTIFICATE')
);

$saveCertificateToPkcs12Action = new SaveCertificateToPkcs12Action(
    certificate: new Certificate($certificateToPem, [], $csr->privateKey),
    path: $_ENV['PATH_TO_KSEF_CERTIFICATE'], // .p12 file
    passphrase: $_ENV['KSEF_CERTIFICATE_PASSPHRASE']
);

new SaveCertificateToPkcs12Handler()->handle($saveCertificateToPkcs12Action);
```

### Send an invoice and check for UPO

### Fetch invoices using encryption key

## Testing

The package uses unit tests via [PHPUnit](https://github.com/sebastianbergmann/phpunit). 

TestCase is located in the location of ```src/Testing/AbstractTestCase```

Fake request and responses fixtures for resources are located in the location of ```src/Testing/Fixtures/Requests```

Run all tests:

```bash
composer install
```

```bash
vendor/bin/phpunit
```

## Roadmap

1. Batch endpoints
2. Prepare the package for release candidate

## Special thanks

Special thanks to:

- all the helpful people on the [4programmers.net](https://4programmers.net/Forum/Nietuzinkowe_tematy/355933-krajowy_system_e_faktur) forum
- authors of the repository [grafinet/xades-tools](https://github.com/grafinet/xades-tools) for the Xades document signing tool
