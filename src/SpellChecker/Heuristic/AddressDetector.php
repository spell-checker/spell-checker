<?php declare(strict_types = 1);
// spell-check-ignore: mathiasbynens urls stavy objednavky zmena dorucovaci adresy noscreen odhlasit nebo zmenit odebirane emaily hodnoceni adventny kalendar HRADEC KRALOVE

namespace SpellChecker\Heuristic;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\RowHelper;
use SpellChecker\Word;

/**
 * Identifies identifiers (classes, ids, constants...) and tries to match them against dictionaries without diacritics
 */
class AddressDetector implements \SpellChecker\Heuristic\Heuristic
{

    // https://mathiasbynens.be/demo/url-regex
    // selected one with false positive behavior, because we need to match even urls formatted with sprintf etc.
    private const URL_REGEX = '~((https?|ftp)://|www\.)(-\.)?([^\s/?\.#]+\.?)+(/[^\s]*)?~i';

    private const EMAIL_REGEX = '~[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+~';

    public const RESULT_EMAIL = 'email';
    public const RESULT_URL = 'url';
    public const RESULT_URL_PART = 'url-part';

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    /** @var bool */
    private $ignoreUrls;

    /** @var bool */
    private $ignoreEmails;

    public function __construct(
        DictionaryCollection $dictionaries,
        bool $ignoreUrls = false,
        bool $ignoreEmails = false
    )
    {
        $this->dictionaries = $dictionaries;
        $this->ignoreUrls = $ignoreUrls;
        $this->ignoreEmails = $ignoreEmails;
    }

    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        if ($word->row === null) {
            $word->row = RowHelper::getRowAtPosition($string, $word->position);
        }

        // words used in an URL may not use diacritics
        if (preg_match_all(self::URL_REGEX, $word->row, $matches)) {
            foreach ($matches[0] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->ignoreUrls) {
                        return self::RESULT_URL;
                    }
                    if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED | DictionarySearch::TRY_WITHOUT_DIACRITICS)) {
                        return self::RESULT_URL;
                    }
                }
            }
        }

        // words used in an e-mail address may not use diacritics
        if (preg_match_all(self::EMAIL_REGEX, $word->row, $matches)) {
            foreach ($matches[0] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->ignoreEmails) {
                        return self::RESULT_EMAIL;
                    }
                    if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED | DictionarySearch::TRY_WITHOUT_DIACRITICS)) {
                        return self::RESULT_EMAIL;
                    }
                }
            }
        }

        // msgid "URL: ajax-hodnoceni"
        // msgstr "URL: adventny-kalendar"
        if (preg_match_all('/(?:msgid|msgstr) "URL: ([^"]+)"/', $word->row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED | DictionarySearch::TRY_WITHOUT_DIACRITICS)) {
                        return self::RESULT_URL_PART;
                    }
                }
            }
        }

        return null;
    }

}
