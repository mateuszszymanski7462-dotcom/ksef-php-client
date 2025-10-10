<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts;

interface ConfigInterface
{
    /**
     * @var int
     */
    public const BATCH_MAX_FILE_SIZE = 5368709120; // 5 GB

    /**
     * @var int
     */
    public const BATCH_MAX_PART_SIZE = 104857600; // 100 MB
}
