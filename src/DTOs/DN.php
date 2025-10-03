<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs;

use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\Support\Optional;
use SensitiveParameter;

final readonly class DN extends AbstractDTO
{
    public function __construct(
        #[SensitiveParameter]
        public string $commonName,
        #[SensitiveParameter]
        public string $countryName,
        #[SensitiveParameter]
        public Optional | string | null $givenName = new Optional(),
        #[SensitiveParameter]
        public Optional | string | null $surname = new Optional(),
        #[SensitiveParameter]
        public Optional | string | null $serialNumber = new Optional(),
        #[SensitiveParameter]
        public Optional | string | null $uniqueIdentifier = new Optional(),
        #[SensitiveParameter]
        public Optional | string | null $organizationName = new Optional(),
        #[SensitiveParameter]
        public Optional | string | null $organizationIdentifier = new Optional(),
    ) {
    }
}
