<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo;

use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\Requests\ValueObjects\ReferenceNumber;

final readonly class UpoRequest extends AbstractRequest
{
    public function __construct(
        public ReferenceNumber $referenceNumber,
        public ReferenceNumber $invoiceReferenceNumber
    ) {
    }
}
