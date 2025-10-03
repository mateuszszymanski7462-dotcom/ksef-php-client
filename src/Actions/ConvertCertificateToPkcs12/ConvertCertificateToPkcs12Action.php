<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Actions\ConvertCertificateToPkcs12;

use N1ebieski\KSEFClient\Actions\AbstractAction;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use SensitiveParameter;

final readonly class ConvertCertificateToPkcs12Action extends AbstractAction
{
    public function __construct(
        #[SensitiveParameter]
        public Certificate $certificate,
        #[SensitiveParameter]
        public ?string $passphrase = null
    ) {
    }
}
