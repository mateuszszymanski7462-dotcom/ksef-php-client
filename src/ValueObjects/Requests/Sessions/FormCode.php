<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\ValueObjects\Requests\Sessions;

use N1ebieski\KSEFClient\Contracts\EnumInterface;

enum FormCode: string implements EnumInterface
{
    case Fa3 = 'FA (3)';

    case Pef3 = 'PEF (3)';

    case KorPef3 = 'KOR_PEF (3)';

    public function getSchemaVersion(): string
    {
        return match ($this) {
            self::Fa3 => '1-0E',
            self::Pef3, self::KorPef3 => '2-1',
        };
    }

    public function getValue(): string
    {
        return match ($this) {
            self::Fa3 => 'FA',
            self::Pef3, self::KorPef3 => 'PEF',
        };
    }

    public function getWariantFormularza(): string
    {
        return '3';
    }
}
