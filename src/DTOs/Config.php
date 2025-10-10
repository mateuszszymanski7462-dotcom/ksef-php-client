<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs;

use N1ebieski\KSEFClient\Contracts\ConfigInterface;
use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\EncryptionKey;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\BaseUri;
use N1ebieski\KSEFClient\ValueObjects\RefreshToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;

final class Config extends AbstractDTO implements ConfigInterface
{
    public function __construct(
        public readonly BaseUri $baseUri,
        public readonly int $asyncMaxConcurrency = 8,
        public readonly ?AccessToken $accessToken = null,
        public readonly ?RefreshToken $refreshToken = null,
        public readonly ?EncryptionKey $encryptionKey = null,
        public readonly ?EncryptedKey $encryptedKey = null,
        public readonly ?Certificate $certificate = null,
    ) {
    }

    public function withEncryptedKey(EncryptedKey $encryptedKey): self
    {
        /** @var array<string, mixed> $data */
        $data = $this->toArray();

        return self::from([
            ...$data,
            'encryptedKey' => $encryptedKey
        ]);
    }

    public function withAccessToken(AccessToken $accessToken): self
    {
        /** @var array<string, mixed> $data */
        $data = $this->toArray();

        return self::from([
            ...$data,
            'accessToken' => $accessToken
        ]);
    }

    public function withoutAccessToken(): self
    {
        /** @var array<string, mixed> $data */
        $data = $this->toArray();

        return self::from([
            ...$data,
            'accessToken' => null
        ]);
    }

    public function withRefreshToken(RefreshToken $refreshToken): self
    {
        /** @var array<string, mixed> $data */
        $data = $this->toArray();

        return self::from([
            ...$data,
            'refreshToken' => $refreshToken
        ]);
    }
}
