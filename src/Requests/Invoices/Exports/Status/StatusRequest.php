<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Invoices\Exports\Status;

use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;

final readonly class StatusRequest extends AbstractRequest
{
    public function __construct(
        public ReferenceNumber $operationReferenceNumber,
    ) {
    }
}
