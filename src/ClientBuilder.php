<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient;

use DateTimeImmutable;
use DateTimeInterface;
use Http\Discovery\Psr18ClientDiscovery;
use InvalidArgumentException;
use N1ebieski\KSEFClient\Actions\ConvertDerToPem\ConvertDerToPemAction;
use N1ebieski\KSEFClient\Actions\ConvertDerToPem\ConvertDerToPemHandler;
use N1ebieski\KSEFClient\Contracts\HttpClient\ClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\DTOs\Requests\Auth\ContextIdentifierGroup;
use N1ebieski\KSEFClient\DTOs\Requests\Auth\XadesSignature;
use N1ebieski\KSEFClient\Factories\ClientFactory;
use N1ebieski\KSEFClient\Factories\EncryptedKeyFactory;
use N1ebieski\KSEFClient\Factories\EncryptedTokenFactory;
use N1ebieski\KSEFClient\Factories\LoggerFactory;
use N1ebieski\KSEFClient\HttpClient\HttpClient;
use N1ebieski\KSEFClient\Requests\Auth\KsefToken\KsefTokenRequest;
use N1ebieski\KSEFClient\Requests\Auth\Status\StatusRequest;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureRequest;
use N1ebieski\KSEFClient\Resources\ClientResource;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\ApiUrl;
use N1ebieski\KSEFClient\ValueObjects\CertificatePath;
use N1ebieski\KSEFClient\ValueObjects\EncryptionKey;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\BaseUri;
use N1ebieski\KSEFClient\ValueObjects\InternalId;
use N1ebieski\KSEFClient\ValueObjects\KsefPublicKey;
use N1ebieski\KSEFClient\ValueObjects\KsefToken;
use N1ebieski\KSEFClient\ValueObjects\LogPath;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\NIP;
use N1ebieski\KSEFClient\ValueObjects\NipVatUe;
use N1ebieski\KSEFClient\ValueObjects\PeppolId;
use N1ebieski\KSEFClient\ValueObjects\RefreshToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\Auth\Challenge;
use N1ebieski\KSEFClient\ValueObjects\Requests\Auth\SubjectIdentifierType;
use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;
use N1ebieski\KSEFClient\ValueObjects\Requests\Security\PublicKeyCertificates\PublicKeyCertificateUsage;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;
use Psr\Http\Client\ClientInterface as BaseClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;

final class ClientBuilder
{
    private ClientInterface $httpClient;

    private ?LoggerInterface $logger = null;

    private Mode $mode = Mode::Production;

    private ApiUrl $apiUrl;

    private ?KsefToken $ksefToken = null;

    private ?AccessToken $accessToken = null;

    private ?RefreshToken $refreshToken = null;

    private ?CertificatePath $certificatePath = null;

    private NIP | NipVatUe | InternalId | PeppolId $identifier;

    private ?EncryptionKey $encryptionKey = null;

    private Optional | bool $verifyCertificateChain;

    private int $asyncMaxConcurrency = 8;

    public function __construct()
    {
        $this->httpClient = ClientFactory::make(Psr18ClientDiscovery::find());
        $this->logger = LoggerFactory::make();
        $this->apiUrl = $this->mode->getApiUrl();
        $this->verifyCertificateChain = new Optional();
    }

    public function withMode(Mode | string $mode): self
    {
        if ($mode instanceof Mode === false) {
            $mode = Mode::from($mode);
        }

        $this->mode = $mode;

        $this->apiUrl = $this->mode->getApiUrl();

        if ($this->mode->isEquals(Mode::Test)) {
            $this->identifier = new NIP('1111111111');
        }

        return $this;
    }


    public function withEncryptionKey(EncryptionKey | string $encryptionKey, ?string $iv = null): self
    {
        if (is_string($encryptionKey)) {
            if ($iv === null) {
                throw new InvalidArgumentException('IV is required when key is string.');
            }

            $encryptionKey = new EncryptionKey($encryptionKey, $iv);
        }

        $this->encryptionKey = $encryptionKey;

        return $this;
    }

    public function withApiUrl(ApiUrl | string $apiUrl): self
    {
        if ($apiUrl instanceof ApiUrl === false) {
            $apiUrl = ApiUrl::from($apiUrl);
        }

        $this->apiUrl = $apiUrl;

        return $this;
    }

    public function withKsefToken(KsefToken | string $ksefToken): self
    {
        if ($ksefToken instanceof KsefToken === false) {
            $ksefToken = KsefToken::from($ksefToken);
        }

        $this->certificatePath = null;

        $this->ksefToken = $ksefToken;

        return $this;
    }

    public function withAccessToken(AccessToken | string $accessToken, DateTimeInterface | string | null $validUntil = null): self
    {
        if ($accessToken instanceof AccessToken === false) {
            if (is_string($validUntil)) {
                $validUntil = new DateTimeImmutable($validUntil);
            }

            $accessToken = AccessToken::from($accessToken, $validUntil);
        }

        $this->accessToken = $accessToken;

        return $this;
    }

    public function withRefreshToken(RefreshToken | string $refreshToken, DateTimeInterface | string | null $validUntil = null): self
    {
        if ($refreshToken instanceof RefreshToken === false) {
            if (is_string($validUntil)) {
                $validUntil = new DateTimeImmutable($validUntil);
            }

            $refreshToken = RefreshToken::from($refreshToken, $validUntil);
        }

        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function withCertificatePath(CertificatePath | string $certificatePath, ?string $passphrase = null): self
    {
        if ($certificatePath instanceof CertificatePath === false) {
            $certificatePath = CertificatePath::from($certificatePath, $passphrase);
        }

        $this->ksefToken = null;

        $this->certificatePath = $certificatePath;

        return $this;
    }

    public function withHttpClient(BaseClientInterface $client): self
    {
        $this->httpClient = ClientFactory::make($client);

        return $this;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withIdentifier(NIP | NipVatUe | InternalId | PeppolId | string $identifier): self
    {
        if (is_string($identifier)) {
            $identifier = NIP::from($identifier);
        }

        $this->identifier = $identifier;

        return $this;
    }

    public function withVerifyCertificateChain(bool $verifyCertificateChain): self
    {
        $this->verifyCertificateChain = $verifyCertificateChain;

        return $this;
    }

    public function withAsyncMaxConcurrency(int $asyncMaxConcurrency): self
    {
        $this->asyncMaxConcurrency = $asyncMaxConcurrency;

        return $this;
    }

    /**
     * @param null|LogLevel::* $level
     */
    public function withLogPath(LogPath | string | null $logPath, ?string $level = LogLevel::DEBUG): self
    {
        if (is_string($logPath)) {
            $logPath = LogPath::from($logPath);
        }

        $this->logger = null;

        if ($level !== null) {
            $this->logger = LoggerFactory::make($logPath, $level);
        }

        return $this;
    }

    public function build(): ClientResourceInterface
    {
        $config = new Config(
            baseUri: new BaseUri($this->apiUrl->value),
            asyncMaxConcurrency: $this->asyncMaxConcurrency,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            encryptionKey: $this->encryptionKey,
        );

        $httpClient = new HttpClient(
            client: $this->httpClient,
            config: $config,
            logger: $this->logger
        );

        $client = new ClientResource($httpClient, $config, $this->logger);

        if ($this->encryptionKey instanceof EncryptionKey) {
            $client = $client->withEncryptedKey($this->handleEncryptedKey($client));
        }

        if ($this->isAuthorisation()) {
            $authorisationAccessResponse = match (true) { //@phpstan-ignore-line
                $this->certificatePath instanceof CertificatePath => $this->handleAuthorisationByCertificate($client),
                $this->ksefToken instanceof KsefToken => $this->handleAuthorisationByKsefToken($client),
            };

            /** @var object{referenceNumber: string, authenticationToken: object{token: string}} $authorisationAccessResponse */
            $authorisationAccessResponse = $authorisationAccessResponse->object();

            $client = $client->withAccessToken(AccessToken::from($authorisationAccessResponse->authenticationToken->token));

            Utility::retry(function () use ($client, $authorisationAccessResponse) {
                /** @var object{status: object{code: int, description: string}} $authorisationStatusResponse */
                $authorisationStatusResponse = $client->auth()->status(
                    new StatusRequest(ReferenceNumber::from($authorisationAccessResponse->referenceNumber))
                )->object();

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

            /** @var object{refreshToken: object{token: string, validUntil: string}, accessToken: object{token: string, validUntil: string}} $authorisationTokenResponse */
            $authorisationTokenResponse = $client->auth()->token()->redeem()->object();

            $client = $client
                ->withAccessToken(AccessToken::from(
                    token: $authorisationTokenResponse->accessToken->token,
                    validUntil: new DateTimeImmutable($authorisationTokenResponse->accessToken->validUntil)
                ))
                ->withRefreshToken(RefreshToken::from(
                    token: $authorisationTokenResponse->refreshToken->token,
                    validUntil: new DateTimeImmutable($authorisationTokenResponse->refreshToken->validUntil)
                ));
        }

        return $client;
    }

    private function isAuthorisation(): bool
    {
        return ! $this->accessToken instanceof AccessToken && (
            $this->ksefToken instanceof KsefToken || $this->certificatePath instanceof CertificatePath
        );
    }

    private function handleEncryptedKey(ClientResourceInterface $client): EncryptedKey
    {
        if ($this->encryptionKey instanceof EncryptionKey === false) {
            throw new RuntimeException('Encryption key is not set');
        }

        $securityResponse = $client->security()->publicKeyCertificates();

        $firstSymmetricKeyEncryptionCertificate = $securityResponse->getFirstByPublicKeyCertificateUsage(PublicKeyCertificateUsage::SymmetricKeyEncryption);

        if ($firstSymmetricKeyEncryptionCertificate === null) {
            throw new RuntimeException('Symmetric key encryption certificate is not found');
        }

        $symmetricKeyEncryptionCertificate = base64_decode($firstSymmetricKeyEncryptionCertificate);

        $certificate = (new ConvertDerToPemHandler())->handle(new ConvertDerToPemAction(
            der: $symmetricKeyEncryptionCertificate,
            name: 'CERTIFICATE'
        ));

        $ksefPublicKey = KsefPublicKey::from($certificate);

        return EncryptedKeyFactory::make($this->encryptionKey, $ksefPublicKey);
    }

    private function handleAuthorisationByCertificate(ClientResourceInterface $client): ResponseInterface
    {
        if ( ! $this->certificatePath instanceof CertificatePath) {
            throw new RuntimeException('Certificate path is not set');
        }

        /** @var object{challenge: string, timestamp: string} $challengeResponse */
        $challengeResponse = $client->auth()->challenge()->object();

        return $client->auth()->xadesSignature(
            new XadesSignatureRequest(
                certificatePath: $this->certificatePath,
                xadesSignature: new XadesSignature(
                    challenge: Challenge::from($challengeResponse->challenge),
                    contextIdentifierGroup: ContextIdentifierGroup::fromIdentifier($this->identifier),
                    subjectIdentifierType: SubjectIdentifierType::CertificateSubject
                ),
                verifyCertificateChain: $this->verifyCertificateChain
            )
        );
    }

    private function handleAuthorisationByKsefToken(ClientResourceInterface $client): ResponseInterface
    {
        if ( ! $this->ksefToken instanceof KsefToken) {
            throw new RuntimeException('KSEF token is not set');
        }

        /** @var object{challenge: string, timestamp: string} $challengeResponse */
        $challengeResponse = $client->auth()->challenge()->object();

        $securityResponse = $client->security()->publicKeyCertificates();

        $firstKsefTokenEncryptionCertificate = $securityResponse->getFirstByPublicKeyCertificateUsage(PublicKeyCertificateUsage::KsefTokenEncryption);

        if ($firstKsefTokenEncryptionCertificate === null) {
            throw new RuntimeException('KSEF token encryption certificate is not found');
        }

        $ksefTokenEncryptionCertificate = base64_decode($firstKsefTokenEncryptionCertificate);

        $certificate = (new ConvertDerToPemHandler())->handle(new ConvertDerToPemAction(
            der: $ksefTokenEncryptionCertificate,
            name: 'CERTIFICATE'
        ));

        $ksefPublicKey = KsefPublicKey::from($certificate);

        $encryptedToken = EncryptedTokenFactory::make(
            ksefToken: $this->ksefToken,
            timestamp: new DateTimeImmutable($challengeResponse->timestamp),
            ksefPublicKey: $ksefPublicKey
        );

        return $client->auth()->ksefToken(
            new KsefTokenRequest(
                challenge: Challenge::from($challengeResponse->challenge),
                contextIdentifierGroup: ContextIdentifierGroup::fromIdentifier($this->identifier),
                encryptedToken: $encryptedToken
            )
        );
    }
}
