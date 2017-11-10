<?php declare(strict_types = 1);

namespace SpellChecker;

class DiacriticsHelper
{

    public static function removeDiacritics(string $string): string
    {
        static $transliterator;
        if ($transliterator === null) {
            $transliterator = \Transliterator::create('NFD; [:Mn:] Remove; NFC');
        }

        return $transliterator->transliterate($string);
    }

}
