<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources\Testdata;

use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Limits\LimitsResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Person\PersonResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Subject\SubjectResourceInterface;

interface TestdataResourceInterface
{
    public function subject(): SubjectResourceInterface;

    public function person(): PersonResourceInterface;

    public function limits(): LimitsResourceInterface;
}
