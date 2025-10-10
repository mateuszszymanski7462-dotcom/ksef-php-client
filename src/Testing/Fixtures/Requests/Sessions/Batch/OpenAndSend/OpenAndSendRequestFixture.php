<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Batch\OpenAndSend;

use N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions\FakturaAbstractFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractRequestFixture;

class OpenAndSendRequestFixture extends AbstractRequestFixture
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [
        'formCode' => 'FA (3)',
        'faktury' => [],
        'offlineMode' => false,
    ];

    /**
     * @param array<int, FakturaAbstractFixture> $faktury
     */
    public function withFakturaFixtures(array $faktury): self
    {
        $this->data['faktury'] = array_map(fn (FakturaAbstractFixture $faktura) => $faktura->data, $faktury);

        return $this;
    }
}
