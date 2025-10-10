<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\DTOs\Requests\Sessions;

use DateTimeImmutable;
use N1ebieski\KSEFClient\Testing\Fixtures\AbstractFixture as BaseAbstractFixture;

/**
 * @property array<string, mixed> $data
 */
abstract class FakturaAbstractFixture extends BaseAbstractFixture
{
    public function withTodayDate(): self
    {
        $todayDate = (new DateTimeImmutable())->format('Y-m-d');

        $this->data['fa']['p_1'] = $todayDate;

        if (isset($this->data['fa']['p_6Group']['p_6'])) {
            $this->data['fa']['p_6Group']['p_6'] = $todayDate;
        }

        return $this;
    }

    public function withRandomInvoiceNumber(): self
    {
        $this->data['fa']['p_2'] = strtoupper(uniqid("INV-"));

        return $this;
    }

    public function withNIP(string $nip): self
    {
        $this->data['podmiot1']['daneIdentyfikacyjne']['nip'] = $nip;

        return $this;
    }
}
