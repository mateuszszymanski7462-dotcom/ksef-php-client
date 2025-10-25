<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Actions\ValidateXml;

use N1ebieski\KSEFClient\Actions\AbstractAction;
use N1ebieski\KSEFClient\ValueObjects\SchemaPath;

final class ValidateXmlAction extends AbstractAction
{
    public function __construct(
        public readonly string $document,
        public readonly SchemaPath $schemaPath
    ) {
    }
}
