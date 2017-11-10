<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use Nette\Utils\Strings;
use SpellChecker\DictionaryCollection;
use SpellChecker\Word;

class NoDiacriticsDetector implements \SpellChecker\Heuristic\Heuristic
{

    /** @var \SpellChecker\DictionaryCollection */
    private $dictionaries;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    // https://mathiasbynens.be/demo/url-regex
    // selected one with false positive behavior, because we need to match even urls formatted with sprintf etc.
    const URL_REGEX = '@(https?|ftp)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?@i';

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        $row = substr($string, $word->rowStart, $word->rowEnd - $word->rowStart);

        // words used in an URL may not use diacritics
        if (preg_match_all(self::URL_REGEX, $row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->containsWithoutDiacritics($word->word, $dictionaries)) {
                        return true;
                    }
                }
            }
        }

        // href="#stavy-objednavky"
        // id="zmena-dorucovaci-adresy"
        // class="u-noscreen u-text-center"
        // utm_content => 'odhlasit-nebo-zmenit-odebirane-emaily'
        // ->createUrl('...')
        if (preg_match_all('/(?:href=|id=|class=|utm_content => |->createUrl\\()["\']([^"\']+)["\']/', $row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->containsWithoutDiacritics($word->word, $dictionaries)) {
                        return true;
                    }
                }
            }
        }

        // const public const HRADEC_KRALOVE_REGION
        if (preg_match_all('/(?:const)\\s([^A-Z0-9_]+)\\s/', $row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    $word = Strings::lower($word->word);
                    if ($this->dictionaries->containsWithoutDiacritics($word, $dictionaries)) {
                        return true;
                    }
                    $word = Strings::firstUpper($word);
                    if ($this->dictionaries->containsWithoutDiacritics($word, $dictionaries)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

}
