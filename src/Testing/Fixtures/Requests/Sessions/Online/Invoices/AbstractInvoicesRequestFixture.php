<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Invoices;

use DateTimeImmutable;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractRequestFixture;

abstract class AbstractInvoicesRequestFixture extends AbstractRequestFixture
{
    public function withTodayDate(): self
    {
        $todayDate = new DateTimeImmutable()->format('Y-m-d');

        $this->data['faktura']['fa']['p_1'] = $todayDate;

        if (isset($this->data['faktura']['fa']['p_6Group']['p_6'])) {
            $this->data['faktura']['fa']['p_6Group']['p_6'] = $todayDate;
        }

        return $this;
    }

    public function withRandomInvoiceNumber(): self
    {
        $this->data['faktura']['fa']['p_2'] = strtoupper(uniqid("INV-"));

        return $this;
    }

    public function withNIP(string $nip): self
    {
        $this->data['faktura']['podmiot1']['daneIdentyfikacyjne']['nip'] = $nip;

        return $this;
    }
}
