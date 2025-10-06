<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Invoices\Exports\Init;

use N1ebieski\KSEFClient\Contracts\BodyInterface;
use N1ebieski\KSEFClient\DTOs\Requests\Invoices\Exports\Filters;
use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;

final readonly class InitRequest extends AbstractRequest implements BodyInterface
{
    public function __construct(
        public EncryptedKey $encryptedKey,
        public Filters $filters,
    ) {
    }

    public function toBody(): array
    {
        return [
            'encryption' => [
                'encryptedSymmetricKey' => $this->encryptedKey->key,
                'initializationVector' => $this->encryptedKey->iv
            ],
            'filters' => $this->filters->toArray()
        ];
    }
}
