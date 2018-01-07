<?php declare(strict_types = 1);
// spell-check-ignore: mathiasbynens urls stavy objednavky zmena dorucovaci adresy noscreen odhlasit nebo zmenit odebirane emaily hodnoceni adventny kalendar HRADEC KRALOVE

namespace SpellChecker\Heuristic;

use Nette\Utils\Strings;
use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Word;

class IdentifiersDetector implements \SpellChecker\Heuristic\Heuristic
{

    // https://mathiasbynens.be/demo/url-regex
    // selected one with false positive behavior, because we need to match even urls formatted with sprintf etc.
    private const URL_REGEX = '~((https?|ftp)://|www\.)(-\.)?([^\s/?\.#]+\.?)+(/[^\s]*)?~i';

    private const EMAIL_REGEX = '~[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+~';

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        $row = substr($string, $word->rowStart, $word->rowEnd - $word->rowStart);

        // words used in an URL may not use diacritics
        if (preg_match_all(self::URL_REGEX, $row, $matches)) {
            foreach ($matches[0] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->containsWithoutDiacritics($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED)) {
                        return true;
                    }
                }
            }
        }

        // words used in an URL may not use diacritics
        if (preg_match_all(self::EMAIL_REGEX, $row, $matches)) {
            foreach ($matches[0] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->containsWithoutDiacritics($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED)) {
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
        if (preg_match_all('/(?:href=|id=|class=|->createUrl\\()(["\'])([^\\1]+)\\1/', $row, $matches)) {
            foreach ($matches[2] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->containsWithoutDiacritics($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED)) {
                        return true;
                    }
                }
            }
        }

        // msgid "URL: ajax-hodnoceni"
        // msgstr "URL: adventny-kalendar"
        if (preg_match_all('/(?:msgid|msgstr) "URL: ([^"]+)"/', $row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->containsWithoutDiacritics($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED)) {
                        return true;
                    }
                }
            }
        }

        // public const HRADEC_KRALOVE_REGION
        if (preg_match_all('/(?:const)\\s([^A-Z0-9_]+)\\s/', $row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    $lower = Strings::lower($word->word);
                    if ($this->dictionaries->containsWithoutDiacritics($dictionaries, $lower, $word->context, DictionarySearch::TRY_LOWERCASE | DictionarySearch::TRY_CAPITALIZED)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

}
