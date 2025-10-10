<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Sessions;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\BDO;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\KRS;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\PelnaNazwa;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\REGON;
use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\Support\Optional;

final class Rejestry extends AbstractDTO implements DomSerializableInterface
{
    /**
     * @param KRS|Optional $krs Numer Krajowego Rejestru Sądowego
     * @param BDO|Optional $bdo Numer w Bazie Danych o Odpadach
     * @return void
     */
    public function __construct(
        public readonly Optional | PelnaNazwa $pelnaNazwa = new Optional(),
        public readonly Optional | KRS $krs = new Optional(),
        public readonly Optional | REGON $regon = new Optional(),
        public readonly Optional | BDO $bdo = new Optional(),
    ) {
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rejestry = $dom->createElement('Rejestry');
        $dom->appendChild($rejestry);

        if ($this->pelnaNazwa instanceof PelnaNazwa) {
            $pelnaNazwa = $dom->createElement('PelnaNazwa');
            $pelnaNazwa->appendChild($dom->createTextNode((string) $this->pelnaNazwa));
            $rejestry->appendChild($pelnaNazwa);
        }

        if ($this->krs instanceof KRS) {
            $krs = $dom->createElement('KRS');
            $krs->appendChild($dom->createTextNode((string) $this->krs));
            $rejestry->appendChild($krs);
        }

        if ($this->regon instanceof REGON) {
            $regon = $dom->createElement('REGON');
            $regon->appendChild($dom->createTextNode((string) $this->regon));
            $rejestry->appendChild($regon);
        }

        if ($this->bdo instanceof BDO) {
            $bdo = $dom->createElement('BDO');
            $bdo->appendChild($dom->createTextNode((string) $this->bdo));
            $rejestry->appendChild($bdo);
        }

        return $dom;
    }
}
