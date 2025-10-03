<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Online\Send;

use N1ebieski\KSEFClient\Contracts\BodyInterface;
use N1ebieski\KSEFClient\Contracts\XmlSerializableInterface;
use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Online\DTOs\Faktura;
use N1ebieski\KSEFClient\Requests\ValueObjects\ReferenceNumber;
use N1ebieski\KSEFClient\Support\Optional;

final readonly class SendRequest extends AbstractRequest implements XmlSerializableInterface, BodyInterface
{
    public function __construct(
        public ReferenceNumber $referenceNumber,
        public Faktura $faktura,
        public Optional | bool $offlineMode = new Optional(),
        public Optional | bool | null $hashOfCorrectedInvoice = new Optional()
    ) {
    }

    public function toBody(): array
    {
        return $this->toArray(only: ['offlineMode', 'hashOfCorrectedInvoice']);
    }

    public function toXml(): string
    {
        return $this->faktura->toXml();
    }
}
