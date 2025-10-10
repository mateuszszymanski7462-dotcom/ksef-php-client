<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Sessions;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\Termin;
use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\Support\Optional;

final class TerminPlatnosci extends AbstractDTO implements DomSerializableInterface
{
    /**
     * @param Optional|Termin $termin Termin płatności
     * @param Optional|TerminOpis $terminOpis Opis terminu płatności
     */
    public function __construct(
        public readonly Optional | Termin $termin = new Optional(),
        public readonly Optional | TerminOpis $terminOpis = new Optional(),
    ) {
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $terminPlatnosci = $dom->createElement('TerminPlatnosci');
        $dom->appendChild($terminPlatnosci);

        if ($this->termin instanceof Termin) {
            $termin = $dom->createElement('Termin');
            $termin->appendChild($dom->createTextNode((string) $this->termin));

            $terminPlatnosci->appendChild($termin);
        }

        if ($this->terminOpis instanceof TerminOpis) {
            $terminOpis = $dom->importNode($this->terminOpis->toDom()->documentElement, true);

            $terminPlatnosci->appendChild($terminOpis);
        }

        return $dom;
    }
}
