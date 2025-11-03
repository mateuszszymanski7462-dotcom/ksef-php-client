<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources;

use DateTimeInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Auth\AuthResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Certificates\CertificatesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Invoices\InvoicesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Limits\LimitsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Permissions\PermissionsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Security\SecurityResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Sessions\SessionsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\TestdataResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Tokens\TokensResourceInterface;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\RefreshToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;

interface ClientResourceInterface
{
    public function getAccessToken(): ?AccessToken;

    public function getRefreshToken(): ?RefreshToken;

    public function withEncryptedKey(EncryptedKey $encryptedKey): self;

    public function withAccessToken(AccessToken | string $accessToken, DateTimeInterface | string | null $validUntil = null): self;

    public function withRefreshToken(RefreshToken | string $refreshToken, DateTimeInterface | string | null $validUntil = null): self;

    public function auth(): AuthResourceInterface;

    public function limits(): LimitsResourceInterface;

    public function security(): SecurityResourceInterface;

    public function sessions(): SessionsResourceInterface;

    public function invoices(): InvoicesResourceInterface;

    public function permissions(): PermissionsResourceInterface;

    public function certificates(): CertificatesResourceInterface;

    public function tokens(): TokensResourceInterface;

    public function testdata(): TestdataResourceInterface;
}
