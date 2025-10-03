<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo;

use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\Requests\ValueObjects\KsefNumber;
use N1ebieski\KSEFClient\Requests\ValueObjects\ReferenceNumber;

final readonly class KsefUpoRequest extends AbstractRequest
{
    public function __construct(
        public ReferenceNumber $referenceNumber,
        public KsefNumber $ksefNumber
    ) {
    }
}
