<?php declare(strict_types = 1);
// spell-check-ignore: mathiasbynens urls stavy objednavky zmena dorucovaci adresy noscreen odhlasit nebo zmenit odebirane emaily hodnoceni adventny kalendar HRADEC KRALOVE

namespace SpellChecker\Heuristic;

use Nette\Utils\Strings;
use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\RowHelper;
use SpellChecker\Word;
use function preg_match_all;
use function strrpos;

/**
 * Identifies identifiers (URLs, classes, ids, constants...) and tries to match them against dictionaries without diacritics
 */
class IdentifiersDetector implements \SpellChecker\Heuristic\Heuristic
{

    public const ID = 'id';
    public const CONSTANT = 'constant';

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    /**
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return string|string
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        if ($word->row === null) {
            $word->row = RowHelper::getRowAtPosition($string, $word->position);
        }

        // href="#stavy-objednavky"
        // id="zmena-dorucovaci-adresy"
        // class="u-noscreen u-text-center"
        // utm_content => 'odhlasit-nebo-zmenit-odebirane-emaily'
        // ->createUrl('...')
        if (preg_match_all('/(?:href=|id=|class=|->createUrl\\()(["\'])([^\\1]+)\\1/', $word->row, $matches)) {
            foreach ($matches[2] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED | DictionarySearch::TRY_WITHOUT_DIACRITICS)) {
                        return self::ID;
                    }
                }
            }
        }

        // public const HRADEC_KRALOVE_REGION
        if (preg_match_all('/(?:const)\\s([^A-Z0-9_]+)\\s/', $word->row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    $lower = Strings::lower($word->word);
                    if ($this->dictionaries->contains($dictionaries, $lower, $word->context, DictionarySearch::TRY_LOWERCASE | DictionarySearch::TRY_CAPITALIZED | DictionarySearch::TRY_WITHOUT_DIACRITICS)) {
                        return self::CONSTANT;
                    }
                }
            }
        }

        return null;
    }

}
