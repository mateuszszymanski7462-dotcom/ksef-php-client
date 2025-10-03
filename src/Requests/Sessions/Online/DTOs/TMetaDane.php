<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Online\DTOs;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\Requests\Sessions\Online\ValueObjects\TKlucz;
use N1ebieski\KSEFClient\Requests\Sessions\Online\ValueObjects\TWartosc;
use N1ebieski\KSEFClient\Support\AbstractDTO;

final readonly class TMetaDane extends AbstractDTO implements DomSerializableInterface
{
    public function __construct(
        public TKlucz $tKlucz,
        public TWartosc $tWartosc,
    ) {
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $tMetaDane = $dom->createElement('TMetaDane');
        $dom->appendChild($tMetaDane);

        $tKlucz = $dom->createElement('TKlucz');
        $tKlucz->appendChild($dom->createTextNode((string) $this->tKlucz));

        $tMetaDane->appendChild($tKlucz);

        $tWartosc = $dom->createElement('TWartosc');
        $tWartosc->appendChild($dom->createTextNode((string) $this->tWartosc));

        $tMetaDane->appendChild($tWartosc);

        return $dom;
    }
}
