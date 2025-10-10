<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Sessions;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\DoZaplaty;
use N1ebieski\KSEFClient\Support\AbstractDTO;

final class DoZaplatyGroup extends AbstractDTO implements DomSerializableInterface
{
    /**
     * @param DoZaplaty $doZaplaty Kwota należności do zapłaty równa polu P_15 powiększonemu o Obciazenia i pomniejszonemu o Odliczenia
     */
    public function __construct(
        public readonly DoZaplaty $doZaplaty,
    ) {
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $doZaplatyGroup = $dom->createElement('DoZaplatyGroup');
        $dom->appendChild($doZaplatyGroup);

        $doZaplaty = $dom->createElement('DoZaplaty');
        $doZaplaty->appendChild($dom->createTextNode((string) $this->doZaplaty->value));

        $doZaplatyGroup->appendChild($doZaplaty);

        return $dom;
    }
}
