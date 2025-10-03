<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Online\DTOs;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\Requests\Sessions\Online\ValueObjects\Opis;
use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\Validator\Rules\Array\MaxRule;
use N1ebieski\KSEFClient\Validator\Rules\Array\MinRule;
use N1ebieski\KSEFClient\Validator\Validator;

final readonly class Tabela extends AbstractDTO implements DomSerializableInterface
{
    /**
     * @var Optional|array<int, TMetaDane>
     */
    public Optional | array $tMetaDane;

    /**
     * @var array<int, Wiersz>
     */
    public array $wiersz;

    /**
     * @param TNaglowek $tNaglowek Nagłówek tabeli
     * @param Optional|array<int, TMetaDane> $tMetaDane Dane opisowe dotyczące tabeli
     * @param array<int, Wiersz> $wiersz Wiersze tabeli
     * @return void
     */
    public function __construct(
        public TNaglowek $tNaglowek,
        array $wiersz,
        Optional | array $tMetaDane = new Optional(),
        public Optional | Opis $opis = new Optional(),
        public Optional | Suma $suma = new Optional(),
    ) {
        Validator::validate([
            'tMetaDane' => $tMetaDane,
            'wiersz' => $wiersz,
        ], [
            'tMetaDane' => [new MaxRule(1000)],
            'wiersz' => [new MinRule(1), new MaxRule(1000)],
        ]);

        $this->tMetaDane = $tMetaDane;
        $this->wiersz = $wiersz;
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $tabela = $dom->createElement('Tabela');
        $dom->appendChild($tabela);

        if ( ! $this->tMetaDane instanceof Optional) {
            foreach ($this->tMetaDane as $tMetaDane) {
                $tMetaDane = $dom->importNode($tMetaDane->toDom()->documentElement, true);

                $tabela->appendChild($tMetaDane);
            }
        }

        if ($this->opis instanceof Opis) {
            $opis = $dom->createElement('Opis');
            $opis->appendChild($dom->createTextNode((string) $this->opis));

            $tabela->appendChild($opis);
        }

        $tNaglowek = $dom->importNode($this->tNaglowek->toDom()->documentElement, true);

        $tabela->appendChild($tNaglowek);

        foreach ($this->wiersz as $wiersz) {
            $wiersz = $dom->importNode($wiersz->toDom()->documentElement, true);

            $tabela->appendChild($wiersz);
        }

        if ($this->suma instanceof Suma) {
            $suma = $dom->importNode($this->suma->toDom()->documentElement, true);

            $tabela->appendChild($suma);
        }

        return $dom;
    }
}
