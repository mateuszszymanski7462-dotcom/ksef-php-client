<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Sessions;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\OpisInnegoTransportu;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\TransportInny;
use N1ebieski\KSEFClient\Support\AbstractDTO;

final class TransportInnyGroup extends AbstractDTO implements DomSerializableInterface
{
    /**
     * @param TransportInny $transportInny Znacznik innego rodzaju transportu: 1 - inny rodzaj transportu
     */
    public function __construct(
        public readonly OpisInnegoTransportu $opisInnegoTransportu,
        public readonly TransportInny $transportInny = TransportInny::Default
    ) {
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $transportInnyGroup = $dom->createElement('TransportInnyGroup');
        $dom->appendChild($transportInnyGroup);

        $transportInny = $dom->createElement('TransportInny');
        $transportInny->appendChild($dom->createTextNode((string) $this->transportInny->value));

        $transportInnyGroup->appendChild($transportInny);

        $opisInnegoTransportu = $dom->createElement('OpisInnegoTransportu');
        $opisInnegoTransportu->appendChild($dom->createTextNode((string) $this->opisInnegoTransportu));

        $transportInnyGroup->appendChild($opisInnegoTransportu);

        return $dom;
    }
}
