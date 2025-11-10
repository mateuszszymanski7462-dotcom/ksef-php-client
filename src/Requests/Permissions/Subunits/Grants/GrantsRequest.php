<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Permissions\Subunits\Grants;

use N1ebieski\KSEFClient\Contracts\BodyInterface;
use N1ebieski\KSEFClient\DTOs\Requests\Permissions\ContextIdentifierInternalIdGroup;
use N1ebieski\KSEFClient\DTOs\Requests\Permissions\ContextIdentifierNipGroup;
use N1ebieski\KSEFClient\DTOs\Requests\Permissions\SubjectIdentifierFingerprintGroup;
use N1ebieski\KSEFClient\DTOs\Requests\Permissions\SubjectIdentifierNipGroup;
use N1ebieski\KSEFClient\DTOs\Requests\Permissions\SubjectIdentifierPeselGroup;
use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\Description;

final class GrantsRequest extends AbstractRequest implements BodyInterface
{
    public function __construct(
        public readonly SubjectIdentifierNipGroup | SubjectIdentifierPeselGroup | SubjectIdentifierFingerprintGroup $subjectIdentifierGroup,
        public readonly ContextIdentifierNipGroup | ContextIdentifierInternalIdGroup $contextIdentifierGroup,
        public readonly Description $description,
        public readonly string $subunitName
    ) {
    }

    public function toBody(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->toArray();

        return [
            ...$data,
            'subjectIdentifier' => [
                'type' => $this->subjectIdentifierGroup->getIdentifier()->getType(),
                'value' => (string) $this->subjectIdentifierGroup->getIdentifier(),
            ],
            'contextIdentifier' => [
                'type' => $this->contextIdentifierGroup->getIdentifier()->getType(),
                'value' => (string) $this->contextIdentifierGroup->getIdentifier(),
            ],
        ];
    }
}
