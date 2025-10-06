<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Invoices\Exports\Status;

use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractRequestFixture;

final class StatusRequestFixture extends AbstractRequestFixture
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [
        'operationReferenceNumber' => 'string'
    ];
}
