<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Auth\XadesSignature;

use N1ebieski\KSEFClient\Contracts\ParametersInterface;
use N1ebieski\KSEFClient\Contracts\XmlSerializableInterface;
use N1ebieski\KSEFClient\DTOs\Requests\Auth\XadesSignature;
use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\Concerns\HasToParameters;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\ValueObjects\Certificate;

final class XadesSignatureRequest extends AbstractRequest implements XmlSerializableInterface, ParametersInterface
{
    use HasToParameters;

    public function __construct(
        public readonly Certificate $certificate,
        public readonly XadesSignature $xadesSignature,
        public readonly Optional | bool $verifyCertificateChain = new Optional()
    ) {
    }

    public function toXml(): string
    {
        return $this->xadesSignature->toXml();
    }
}
