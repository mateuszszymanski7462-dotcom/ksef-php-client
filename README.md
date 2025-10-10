
![1920x810](https://github.com/user-attachments/assets/7db28b6a-80fc-4651-9d07-f04aad6ec8c7)

# KSEF PHP Client

> **This package is not production ready yet!**

PHP API client that allows you to interact with the [KSEF API](https://ksef-test.mf.gov.pl/docs/v2/index.html) Krajowy System e-Faktur

Main features:

- Support for authorization using qualified certificates, KSeF certificates, KSeF tokens, and trusted ePUAP signatures (manual mode)
- Support for async batch send multiple invoices
- Logical invoice structure mapped to DTOs and ValueObjects
- Automatic access token refresh
- CSR (Certificate Signing Request) handling
- KSeF exception handling
- QR codes generation

|  KSEF Version  |     Branch     | Release Version |
|:--------------:|:--------------:|:---------------:|
|       2.0      |      main      |      ^0.3       |
|       1.0      |      0.2.x     |      0.2.*      |

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
    - [Security](#security)
        - [Public Key Certificates](#public-key-certificates)
    - [Sessions](#sessions)
        - [Sessions Invoices](#sessions-invoices)
            - [Upo](#upo)
            - [Ksef Upo](#ksef-upo)
            - [Invoices Status](#invoices-status)
        - [Online](#online)
            - [Open](#open)
            - [Close](#close)
            - [Invoices Send](#invoices-send)
        - [Batch](#batch)
            - [Batch open (and batch send multiple invoices)](#batch-open-and-batch-send-multiple-invoices)
            - [Batch close](#batch-close)
        - [Sessions Status](#sessions-status)
    - [Invoices](#invoices)
        - [Invoices Download](#invoices-download)
        - [Query](#query)
            - [Query Metadata](#query-metadata)
        - [Exports](#exports)
            - [Exports Init](#exports-init)
            - [Exposts Status](#exports-status)
    - [Certificates](#certificates)
        - [Limits](#limits)
        - [Enrollments](#enrollments)
            - [Enrollments Data](#enrollments-data)
            - [Enrollments Send](#enrollments-send)
            - [Enrollments Status](#enrollments-status)
        - [Certificates Retrieve](#certificates-retrieve)
        - [Certificates Revoke](#certificates-revoke)
        - [Certificates Query](#certificates-query)
    - [Tokens](#tokens)
        - [Tokens Create](#tokens-create)
        - [Tokens List](#tokens-list)
        - [Tokens Status](#tokens-status)
        - [Tokens Revoke](#tokens-revoke)
    - [Testdata](#testdata)
        - [Person](#person)
            - [Person Create](#person-create)
            - [Person Remove](#person-remove)
- [Examples](#examples)
    - [Generate a KSEF certificate and convert to .p12 file](#generate-a-ksef-certificate-and-convert-to-p12-file)
    - [Send an invoice, check for UPO and generate QR code](#send-an-invoice-check-for-upo-and-generate-qr-code)
    - [Batch async send multiple invoices and check for UPO](#batch-async-send-multiple-invoices-and-check-for-upo)
    - [Create an offline invoice and generate both QR codes](#create-an-offline-invoice-and-generate-both-qr-codes)
    - [Download and decrypt invoices using the encryption key](#download-and-decrypt-invoices-using-the-encryption-key)
- [Testing](#testing)
- [Roadmap](#roadmap)
- [Special thanks](#special-thanks)

## Get Started

> **Requires [PHP 8.1+](https://www.php.net/releases/)**

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

$client = (new ClientBuilder())
    ->withMode(Mode::Production) // Choice between: Test, Demo, Production
    ->withApiUrl($_ENV['KSEF_API_URL']) // Optional, default is set by Mode selection
    ->withHttpClient(new \GuzzleHttp\Client(...)) // Optional PSR-18 implementation, default is set by Psr18ClientDiscovery::find()
    ->withLogger(new \Monolog\Logger(...)) // Optional PSR-3 implementation, default is set by PsrDiscovery\Discover::log()
    ->withLogPath($_ENV['PATH_TO_LOG_FILE'], $_ENV['LOG_LEVEL']) // Optional, level: null disables logging
    ->withAccessToken($_ENV['ACCESS_TOKEN']) // Optional, if present, auto authorization is skipped
    ->withRefreshToken($_ENV['REFRESH_TOKEN']) // Optional, if present, auto refresh access token is enabled
    ->withKsefToken($_ENV['KSEF_TOKEN']) // Required for API Token authorization. Optional otherwise
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE']) // Required .p12 file for Certificate authorization. Optional otherwise
    ->withVerifyCertificateChain(true) // Optional. Explanation https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1xades-signature/post
    ->withEncryptionKey(EncryptionKeyFactory::makeRandom()) // Required for invoice resources. Remember to save this value!
    ->withIdentifier('NIP_NUMBER') // Required for authorization. Optional otherwise
    ->withAsyncMaxConcurrency(8) // Optional. Maximum concurrent send operations during asynchronous sending
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

<details open>
    <summary>
        <h3>Auto authorization via KSEF Token</h3>
    </summary>

```php
use N1ebieski\KSEFClient\ClientBuilder;

$client = (new ClientBuilder())
    ->withKsefToken($_ENV['KSEF_KEY'])
    ->withIdentifier('NIP_NUMBER')
    ->build();

// Do something with the available resources
```
</details>

<details>
    <summary>
        <h3>Auto authorization via certificate .p12</h3>
    </summary>

```php
use N1ebieski\KSEFClient\ClientBuilder;

$client = (new ClientBuilder())
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->withIdentifier('NIP_NUMBER')
    ->build();

// Do something with the available resources
```
</details>

<details>
    <summary>
        <h3>Manual authorization</h3>
    </summary>

```php
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Requests\Auth\DTOs\XadesSignature;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureXmlRequest;

$client = (new ClientBuilder())->build();

$nip = 'NIP_NUMBER';

$authorisationChallengeResponse = $client->auth()->challenge()->object();

$xml = XadesSignature::from([
    'challenge' => $authorisationChallengeResponse->challenge,
    'contextIdentifierGroup' => [
        'identifierGroup' => [
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
</details>

## Resources

### Auth

<details>
    <summary>
        <h4>Challenge</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1challenge/post

```php
$response = $client->auth()->challenge()->object();
```
</details>

<details>
    <summary>
        <h4>Xades Signature</h4>
    </summary>

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
</details>

<details>
    <summary>
        <h4>Auth Status</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1%7BreferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Auth\Status\StatusRequest;

$response = $client->auth()->status(
    new StatusRequest(...)
)->object();
```
</details>

#### Token

<details>
    <summary>
        <h5>Redeem</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1token~1redeem/post

```php
$response = $client->auth()->token()->redeem()->object();
```
</details>

<details>
    <summary>
        <h5>Refresh</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Uzyskiwanie-dostepu/paths/~1api~1v2~1auth~1token~1refresh/post

```php
$response = $client->auth()->token()->refresh()->object();
```
</details>

### Security

<details>
    <summary>
        <h4>Public Key Certificates</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty-klucza-publicznego/paths/~1api~1v2~1security~1public-key-certificates/get

```php
$response = $client->security()->publicKeyCertificates()->object();
```
</details>

### Sessions

#### Sessions Invoices

<details>
    <summary>
        <h5>Upo</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Status-wysylki-i-UPO/paths/~1api~1v2~1sessions~1%7BreferenceNumber%7D~1invoices~1%7BinvoiceReferenceNumber%7D~1upo/get

```php
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo\UpoRequest;

$response = $client->sessions()->invoices()->upo(
    new UpoRequest(...)
)->body();
```
</details>

<details>
    <summary>
        <h5>Ksef Upo</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Status-wysylki-i-UPO/paths/~1api~1v2~1sessions~1%7BreferenceNumber%7D~1invoices~1ksef~1%7BksefNumber%7D~1upo/get

```php
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;

$response = $client->sessions()->invoices()->ksefUpo(
    new KsefUpoRequest(...)
)->body();
```
</details>

<details>
    <summary>
        <h5>Invoices Status</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Status-wysylki-i-UPO/paths/~1api~1v2~1sessions~1%7BreferenceNumber%7D~1invoices~1%7BinvoiceReferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Status\StatusRequest;

$response = $client->sessions()->invoices()->status(
    new StatusRequest(...)
)->object();
```
</details>

#### Online

<details>
    <summary>
        <h5>Open</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Wysylka-interaktywna/paths/~1api~1v2~1sessions~1online/post

```php
use N1ebieski\KSEFClient\Requests\Sessions\Online\Open\OpenRequest;

$response = $client->sessions()->online()->open(
    new OpenRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h5>Close</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Wysylka-interaktywna/paths/~1api~1v2~1sessions~1online~1%7BreferenceNumber%7D~1close/post

```php
use N1ebieski\KSEFClient\Requests\Sessions\Online\Close\CloseRequest;

$response = $client->sessions()->online()->close(
    new CloseRequest(...)
)->status();
```
</details>

<details>
    <summary>
        <h5>Invoices send</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Wysylka-interaktywna/paths/~1api~1v2~1sessions~1online~1%7BreferenceNumber%7D~1invoices/post

for DTO invoice:

```php
use N1ebieski\KSEFClient\Requests\Sessions\Online\Send\SendRequest;

$response = $client->sessions()->online()->send(
    new SendRequest(...)
)->object();
```

for XML invoice:

```php
use N1ebieski\KSEFClient\Requests\Sessions\Online\Send\SendXmlRequest;

$response = $client->sessions()->online()->send(
    new SendXmlRequest(...)
)->object();
```
</details>

#### Batch

<details>
    <summary>
        <h5>Batch open (and batch send multiple invoices)</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Wysylka-wsadowa/paths/~1api~1v2~1sessions~1batch/post

for DTOs invoices:

```php
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendRequest;

$response = $client->sessions()->batch()->openAndSend(
    new OpenAndSendRequest(...)
)->object();
```

for XMLs invoices:

```php
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendXmlRequest;

$response = $client->sessions()->batch()->openAndSend(
    new OpenAndSendXmlRequest(...)
)->object();
```

for ZIP invoices:

```php
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendZipRequest;

$response = $client->sessions()->batch()->openAndSend(
    new OpenAndSendZipRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h5>Batch Close</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Wysylka-wsadowa/paths/~1api~1v2~1sessions~1batch~1%7BreferenceNumber%7D~1close/post

```php
use N1ebieski\KSEFClient\Requests\Sessions\Batch\Close\CloseRequest;

$response = $client->sessions()->batch()->close(
    new CloseRequest(...)
)->status();
```
</details>

<details>
    <summary>
        <h4>Sessions Status</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Status-wysylki-i-UPO/paths/~1api~1v2~1sessions~1%7BreferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Sessions\Status\StatusRequest;

$response = $client->sessions()->status(
    new StatusRequest(...)
)->object();
```
</details>

### Invoices

<details>
    <summary>
        <h4>Invoices Download</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Pobieranie-faktur/paths/~1api~1v2~1invoices~1ksef~1%7BksefNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Invoices\Download\DownloadRequest;

$response = $client->invoices()->download(
    new DownloadRequest(...)
)->body();
```
</details>

#### Query

<details>
    <summary>
        <h5>Query Metadata</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Pobieranie-faktur/paths/~1api~1v2~1invoices~1query~1metadata/post

```php
use N1ebieski\KSEFClient\Requests\Invoices\Query\Metadata\MetadataRequest;

$response = $client->invoices()->query()->metadata(
    new MetadataRequest(...)
)->object();
```
</details>

#### Exports

<details>
    <summary>
        <h5>Exports Init</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Pobieranie-faktur/paths/~1api~1v2~1invoices~1exports/post

```php
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Init\InitRequest;

$response = $client->invoices()->exports()->init(
    new InitRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h5>Exports Status</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Pobieranie-faktur/paths/~1api~1v2~1invoices~1exports~1%7BoperationReferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Status\StatusRequest;

$response = $client->invoices()->exports()->status(
    new StatusRequest(...)
)->object();
```
</details>

### Certificates

<details>
    <summary>
        <h4>Limits</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1limits/get

```php
$response = $client->certificates()->limits()->object();
```
</details>

#### Enrollments

<details>
    <summary>
        <h5>Enrollments Data</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1enrollments~1data/get

```php
$response = $client->certificates()->enrollments()->data()->object();
```
</details>

<details>
    <summary>
        <h5>Enrollments Send</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1enrollments/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Send\SendRequest;

$response = $client->certificates()->enrollments()->send(
    new SendRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h5>Enrollments Status</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1enrollments~1%7BreferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Status\StatusRequest;

$response = $client->certificates()->enrollments()->status(
    new StatusRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h4>Certificates Retrieve</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1retrieve/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Retrieve\RetrieveRequest;

$response = $client->certificates()->retrieve(
    new RetrieveRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h4>Certificates Revoke</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1%7BcertificateSerialNumber%7D~1revoke/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Revoke\RevokeRequest;

$response = $client->certificates()->revoke(
    new RevokeRequest(...)
)->status();
```
</details>

<details>
    <summary>
        <h4>Certificates Query</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1query/post

```php
use N1ebieski\KSEFClient\Requests\Certificates\Query\QueryRequest;

$response = $client->certificates()->query(
    new QueryRequest(...)
)->object();
```
</details>

### Tokens

<details>
    <summary>
        <h4>Tokens Create</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Tokeny-KSeF/paths/~1api~1v2~1tokens/post

```php
use N1ebieski\KSEFClient\Requests\Tokens\Create\CreateRequest;

$response = $client->tokens()->create(
    new CreateRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h4>Tokens List</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Tokeny-KSeF/paths/~1api~1v2~1tokens/get

```php
use N1ebieski\KSEFClient\Requests\Tokens\List\ListRequest;

$response = $client->tokens()->list(
    new ListRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h4>Tokens Status</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Tokeny-KSeF/paths/~1api~1v2~1tokens~1%7BreferenceNumber%7D/get

```php
use N1ebieski\KSEFClient\Requests\Tokens\Status\StatusRequest;

$response = $client->tokens()->list(
    new StatusRequest(...)
)->object();
```
</details>

<details>
    <summary>
        <h4>Tokens Revoke</h4>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Tokeny-KSeF/paths/~1api~1v2~1tokens~1%7BreferenceNumber%7D/delete

```php
use N1ebieski\KSEFClient\Requests\Tokens\Revoke\RevokeRequest;

$response = $client->tokens()->revoke(
    new RevokeRequest(...)
)->status();
```
</details>

### Testdata

#### Person

<details>
    <summary>
        <h5>Person Create</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Dane-testowe/paths/~1api~1v2~1testdata~1person/post

```php
use N1ebieski\KSEFClient\Requests\Testdata\Person\Create\CreateRequest;

$response = $client->testdata()->person()->create(
    new CreateRequest(...)
)->status();
```
</details>

<details>
    <summary>
        <h5>Person Remove</h5>
    </summary>

https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Dane-testowe/paths/~1api~1v2~1testdata~1person~1remove/post

```php
use N1ebieski\KSEFClient\Requests\Testdata\Person\Remove\RemoveRequest;

$response = $client->testdata()->person()->remove(
    new RemoveRequest(...)
)->status();
```
</details>

## Examples

<details>
    <summary>
        <h3>Generate a KSEF certificate and convert to .p12 file</h3>
    </summary>

```php
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
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\PrivateKeyType;

$client = (new ClientBuilder())
    ->withMode(Mode::Test)
    ->withIdentifier('NIP_NUMBER')
    // To generate the KSEF certificate, you have to authorize the qualified certificate the first time
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->build();

$dataResponse = $client->certificates()->enrollments()->data()->json();

$dn = DN::from($dataResponse);

// You can choose beetween EC or RSA private key type
$csr = CSRFactory::make($dn, PrivateKeyType::EC);

$csrToDer = (new ConvertPemToDerHandler())->handle(new ConvertPemToDerAction($csr->raw));

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

$certificateToPem = (new ConvertDerToPemHandler())->handle(
    new ConvertDerToPemAction($certificate, 'CERTIFICATE')
);

$certificateToPkcs12 = (new ConvertCertificateToPkcs12Handler())->handle(
    new ConvertCertificateToPkcs12Action(
        certificate: new Certificate($certificateToPem, [], $csr->privateKey),
        passphrase: 'password'
    )
);

file_put_contents(Utility::basePath('config/certificates/ksef-certificate.p12'), $certificateToPkcs12);
```
</details>

<details>
    <summary>
        <h3>Send an invoice, check for UPO and generate QR code</h3>
    </summary>

```php
<?php

use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesAction;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesHandler;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\DTOs\QRCodes;
use N1ebieski\KSEFClient\DTOs\Requests\Sessions\Faktura;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendRequestFixture;
use N1ebieski\KSEFClient\ValueObjects\Mode;

$encryptionKey = EncryptionKeyFactory::makeRandom();

$client = (new ClientBuilder())
    ->withMode(Mode::Test)
    ->withIdentifier('NIP_NUMBER')
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->withEncryptionKey($encryptionKey)
    ->build();

$openResponse = $client->sessions()->online()->open([
    'formCode' => 'FA (3)',
])->object();

$fakturaFixture = (new FakturaSprzedazyTowaruFixture())->withRandomInvoiceNumber()->withTodayDate();

$fixture = (new SendRequestFixture())->withFakturaFixture($fakturaFixture);

// For sending invoice as DTO use SendRequest DTO or array
// For sending invoice as XML use SendXmlRequest DTO
$sendResponse = $client->sessions()->online()->send([
    ...$fixture->data,
    'referenceNumber' => $openResponse->referenceNumber,
])->object();

$closeResponse = $client->sessions()->online()->close([
    'referenceNumber' => $openResponse->referenceNumber
]);

$statusResponse = Utility::retry(function () use ($client, $openResponse, $sendResponse) {
    $statusResponse = $client->sessions()->invoices()->status([
        'referenceNumber' => $openResponse->referenceNumber,
        'invoiceReferenceNumber' => $sendResponse->referenceNumber
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

$upo = $client->sessions()->invoices()->upo([
    'referenceNumber' => $openResponse->referenceNumber,
    'invoiceReferenceNumber' => $sendResponse->referenceNumber
])->body();

$faktura = Faktura::from($fakturaFixture->data);

$generateQRCodesHandler = new GenerateQRCodesHandler(
    qrCodeBuilder: (new QrCodeBuilder())
        ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
        ->labelFont(new OpenSans(size: 12)),
    convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
);

/** @var QRCodes $qrCodes */
$qrCodes = $generateQRCodesHandler->handle(GenerateQRCodesAction::from([
    'nip' => $faktura->podmiot1->daneIdentyfikacyjne->nip,
    'invoiceCreatedAt' => $faktura->fa->p_1->value,
    'document' => $faktura->toXml(),
    'ksefNumber' => $statusResponse->ksefNumber
]));

// Invoice link
file_put_contents(Utility::basePath("var/qr/code1.png"), $qrCodes->code1);
```
</details>

<details>
    <summary>
        <h3>Batch async send multiple invoices and check for UPO</h3>
    </summary>

```php
<?php

use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\ValueObjects\Mode;

$encryptionKey = EncryptionKeyFactory::makeRandom();

$client = (new ClientBuilder())
    ->withMode(Mode::Test)
    ->withIdentifier('NIP_NUMBER')
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->withEncryptionKey($encryptionKey)
    ->build();

$faktury = array_map(
    fn () => (new FakturaSprzedazyTowaruFixture())
        ->withTodayDate()
        ->withRandomInvoiceNumber()
        ->data,
    range(1, 100)
);

// For sending invoices as DTOs use OpenRequest DTO or array
// For sending invoices as XMLs use OpenXmlRequest DTO
// For sending invoices as ZIP use OpenZipRequest DTO
$openResponse = $client->sessions()->batch()->openAndSend([
    'formCode' => 'FA (3)',
    'faktury' => $faktury
])->object();

$client->sessions()->batch()->close([
    'referenceNumber' => $openResponse->referenceNumber
]);

$statusResponse = Utility::retry(function () use ($client, $openResponse) {
    $statusResponse = $client->sessions()->status([
        'referenceNumber' => $openResponse->referenceNumber,
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

$upo = file_get_contents($statusResponse->upo->pages[0]->downloadUrl);
```

</details>

<details>
    <summary>
        <h3>Create an offline invoice and generate both QR codes</h3>
    </summary>

```php
<?php

use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesAction;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesHandler;
use N1ebieski\KSEFClient\DTOs\QRCodes;
use N1ebieski\KSEFClient\DTOs\Requests\Sessions\Faktura;
use N1ebieski\KSEFClient\Factories\CertificateFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaSprzedazyTowaruFixture;
use N1ebieski\KSEFClient\ValueObjects\CertificatePath;

$nip = 'NIP_NUMBER';

// From https://ksef-test.mf.gov.pl/docs/v2/index.html#tag/Certyfikaty/paths/~1api~1v2~1certificates~1query/post
$certificateSerialNumber = $_ENV['CERTIFICATE_SERIAL_NUMBER'];
// Remember: this certificate must be "Offline" type, not "Authentication"
$certificate = CertificateFactory::make(
    CertificatePath::from($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
);

$fakturaFixture = (new FakturaSprzedazyTowaruFixture())->withTodayDate()->withRandomInvoiceNumber();

$faktura = Faktura::from($fakturaFixture->data);

$generateQRCodesHandler = new GenerateQRCodesHandler(
    qrCodeBuilder: (new QrCodeBuilder())
        ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
        ->labelFont(new OpenSans(size: 12)),
    convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
);

/** @var QRCodes $qrCodes */
$qrCodes = $generateQRCodesHandler->handle(GenerateQRCodesAction::from([
    'nip' => $faktura->podmiot1->daneIdentyfikacyjne->nip,
    'invoiceCreatedAt' => $faktura->fa->p_1->value,
    'document' => $faktura->toXml(),
    'certificate' => $certificate,
    'certificateSerialNumber' => $certificateSerialNumber,
    'contextIdentifierGroup' => [
        'identifierGroup' => [
            'nip' => $nip
        ]
    ]
]));

// Invoice link
file_put_contents(Utility::basePath("var/qr/code1.png"), $qrCodes->code1);

// Certificate verification link
file_put_contents(Utility::basePath("var/qr/code2.png"), $qrCodes->code2);
```

</details>

<details>
    <summary>
        <h3>Download and decrypt invoices using the encryption key</h3>
    </summary>

```php
<?php

use N1ebieski\KSEFClient\Actions\DecryptDocument\DecryptDocumentAction;
use N1ebieski\KSEFClient\Actions\DecryptDocument\DecryptDocumentHandler;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\Mode;

$encryptionKey = EncryptionKeyFactory::makeRandom();

$client = (new ClientBuilder())
    ->withMode(Mode::Test)
    ->withIdentifier($_ENV['NIP_NUMBER'])
    ->withCertificatePath($_ENV['PATH_TO_CERTIFICATE'], $_ENV['CERTIFICATE_PASSPHRASE'])
    ->withEncryptionKey($encryptionKey)
    ->build();

$initResponse = $client->invoices()->exports()->init([
    'filters' => [
        'subjectType' => 'Subject1',
        'dateRange' => [
            'dateType' => 'Invoicing',
            'from' => new DateTimeImmutable('-1 day'),
            'to' => new DateTimeImmutable()
        ],
    ]
])->object();

$statusResponse = Utility::retry(function () use ($client, $initResponse) {
    $statusResponse = $client->invoices()->exports()->status([
        'operationReferenceNumber' => $initResponse->operationReferenceNumber
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

$decryptDocumentHandler = new DecryptDocumentHandler();

// Downloading...
foreach ($statusResponse->package->parts as $part) {
    $contents = file_get_contents($part->url);

    $contents = $decryptDocumentHandler->handle(new DecryptDocumentAction(
        document: $contents,
        encryptionKey: $encryptionKey
    ));

    $name = rtrim($part->partName, '.aes');

    file_put_contents(Utility::basePath("var/zip/{$name}"), $contents);
}
```
</details>

## Testing

The package uses unit tests via [Pest](https://pestphp.com). 

Pest configuration is located in ```tests/Pest```

TestCase is located in ```tests/AbstractTestCase```

Fake request and responses fixtures for resources are located in ```src/Testing/Fixtures/Requests```

Run all tests:

```bash
composer install
```

```bash
vendor/bin/pest
```

## Roadmap

1. Implementation of other endpoints
2. Prepare the package for release candidate

## Special thanks

Special thanks to:

- all the helpful people on the [4programmers.net](https://4programmers.net/Forum/Nietuzinkowe_tematy/355933-krajowy_system_e_faktur) forum
- authors of the repository [grafinet/xades-tools](https://github.com/grafinet/xades-tools) for the Xades document signing tool
