<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send;

use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaAbstractFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractRequestFixture;

class SendRequestFixture extends AbstractRequestFixture
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [
        'referenceNumber' => '20250625-EE-319D7EE000-B67F415CDC-2C',
    ];

    public function withFakturaFixture(FakturaAbstractFixture $faktura): self
    {
        $this->data['faktura'] = $faktura->data;

        return $this;
    }
}
