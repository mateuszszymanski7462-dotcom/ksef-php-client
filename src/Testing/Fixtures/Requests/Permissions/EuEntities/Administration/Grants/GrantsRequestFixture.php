<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Permissions\EuEntities\Administration\Grants;

use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractRequestFixture;

final class GrantsRequestFixture extends AbstractRequestFixture
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [
        'subjectIdentifierGroup' => [
            'fingerprint' => 'CEB3643BAC2C111ADDE971BDA5A80163441867D65389FC0BC0DFF8B4C1CD4E59',
        ],
        'contextIdentifierGroup' => [
            'nipVatUe' => '7762811692-DE123456789',
        ],
        'description' => 'Opis uprawnienia',
        'euEntityName' => [
            'euSubjectName' => 'Firma G.m.b.H.'
        ],
    ];
}
