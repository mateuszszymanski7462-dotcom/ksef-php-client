<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Auth\AuthResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Certificates\CertificatesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Invoices\InvoicesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Limits\LimitsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\PermissionsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Security\SecurityResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\SessionsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\TestdataResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Tokens\TokensResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Requests\Auth\Token\Refresh\RefreshHandler;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Auth\AuthResource;
use N1ebieski\KSEFClient\Resources\Certificates\CertificatesResource;
use N1ebieski\KSEFClient\Resources\Invoices\InvoicesResource;
use N1ebieski\KSEFClient\Resources\Limits\LimitsResource;
use N1ebieski\KSEFClient\Resources\Permissions\PermissionsResource;
use N1ebieski\KSEFClient\Resources\Security\SecurityResource;
use N1ebieski\KSEFClient\Resources\Sessions\SessionsResource;
use N1ebieski\KSEFClient\Resources\Testdata\TestdataResource;
use N1ebieski\KSEFClient\Resources\Tokens\TokensResource;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\RefreshToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class ClientResource extends AbstractResource implements ClientResourceInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private Config $config,
        private readonly ExceptionHandlerInterface $exceptionHandler,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function getAccessToken(): ?AccessToken
    {
        return $this->config->accessToken;
    }

    public function getRefreshToken(): ?RefreshToken
    {
        return $this->config->refreshToken;
    }

    public function withEncryptedKey(EncryptedKey $encryptedKey): self
    {
        $this->client = $this->client->withEncryptedKey($encryptedKey);
        $this->config = $this->config->withEncryptedKey($encryptedKey);

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

        $this->client = $this->client->withAccessToken($accessToken);
        $this->config = $this->config->withAccessToken($accessToken);

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

        $this->config = $this->config->withRefreshToken($refreshToken);

        return $this;
    }

    private function refreshTokenIfExpired(): void
    {
        if ($this->config->accessToken?->isExpired('-1 minute') === true) {
            if ($this->config->refreshToken?->isExpired() === false) {
                $this->withAccessToken(AccessToken::from($this->config->refreshToken->token));

                /** @var object{accessToken: object{token: string, validUntil: string}} $authorisationTokenResponse */
                $authorisationTokenResponse = (new RefreshHandler($this->client))->handle()->object();

                $this->withAccessToken(AccessToken::from(
                    token: $authorisationTokenResponse->accessToken->token,
                    validUntil: new DateTimeImmutable($authorisationTokenResponse->accessToken->validUntil)
                ));

                return;
            }

            throw new RuntimeException('Access token and refresh token are expired.');
        }
    }

    public function auth(): AuthResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new AuthResource($this->client, $this->config, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function limits(): LimitsResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new LimitsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function security(): SecurityResourceInterface
    {
        try {
            return new SecurityResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function sessions(): SessionsResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new SessionsResource($this->client, $this->config, $this->exceptionHandler, $this->logger);
        } catch (Exception $exception) {
            throw $this->exceptionHandler->handle($exception);
        }
    }

    public function invoices(): InvoicesResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new InvoicesResource($this->client, $this->config, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function permissions(): PermissionsResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new PermissionsResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function certificates(): CertificatesResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new CertificatesResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function tokens(): TokensResourceInterface
    {
        try {
            $this->refreshTokenIfExpired();

            return new TokensResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function testdata(): TestdataResourceInterface
    {
        try {
            return new TestdataResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }
}
