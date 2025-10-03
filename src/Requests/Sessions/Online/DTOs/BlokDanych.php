<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Sessions\Online\DTOs;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\Requests\Sessions\Online\ValueObjects\ZNaglowek;
use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\Validator\Rules\Array\MaxRule;
use N1ebieski\KSEFClient\Validator\Rules\Array\MinRule;
use N1ebieski\KSEFClient\Validator\Validator;

final readonly class BlokDanych extends AbstractDTO implements DomSerializableInterface
{
    /**
     * @var array<int, MetaDane>
     */
    public array $metaDane;

    /**
     * @var Optional|array<int, Tabela>
     */
    public Optional | array $tabela;

    /**
     * @param Optional|ZNaglowek $zNaglowek Nagłówek bloku danych
     * @param array<int, MetaDane> $metaDane Dane opisowe bloku danych
     * @param Optional|Tekst $tekst Część tekstowa bloku danych
     * @param Optional|array<int, Tabela> $tabela Tabele
     * @return void
     */
    public function __construct(
        array $metaDane,
        public Optional | ZNaglowek $zNaglowek = new Optional(),
        public Optional | Tekst $tekst = new Optional(),
        Optional | array $tabela = new Optional(),
    ) {
        Validator::validate([
            'metaDane' => $metaDane,
            'tabela' => $tabela,
        ], [
            'metaDane' => [new MinRule(1), new MaxRule(1000)],
            'tabela' => [new MaxRule(1000)],
        ]);

        $this->metaDane = $metaDane;
        $this->tabela = $tabela;
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $blokDanych = $dom->createElement('BlokDanych');
        $dom->appendChild($blokDanych);

        if ($this->zNaglowek instanceof ZNaglowek) {
            $zNaglowek = $dom->createElement('ZNaglowek');
            $zNaglowek->appendChild($dom->createTextNode((string) $this->zNaglowek));

            $blokDanych->appendChild($zNaglowek);
        }

        foreach ($this->metaDane as $metaDane) {
            $metaDane = $dom->importNode($metaDane->toDom()->documentElement, true);

            $blokDanych->appendChild($metaDane);
        }

        if ($this->tekst instanceof Tekst) {
            $tekst = $dom->importNode($this->tekst->toDom()->documentElement, true);

            $blokDanych->appendChild($tekst);
        }

        if ( ! $this->tabela instanceof Optional) {
            foreach ($this->tabela as $tabela) {
                $tabela = $dom->importNode($tabela->toDom()->documentElement, true);

                $blokDanych->appendChild($tabela);
            }
        }

        return $dom;
    }
}
