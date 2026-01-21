<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;
use function ctype_digit;
use function preg_match;

/**
 * Filters characters, that are probably a formatting character in printf function
 */
class PrintfDetector implements Heuristic
{

    public const string RESULT_PRINTF = 'printf';

    /**
     * @param string[] $dictionaries
     * @return string|string
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        if (preg_match('/^[bcdeEfFgGosuxX]/', $word->word)) {
            $char1 = $string[$word->position - 1];
            // "%d"
            if ($char1 === '%') {
                return self::RESULT_PRINTF;
            }
            // "%1$d"
            $char2 = $string[$word->position - 2];
            $char3 = $string[$word->position - 3];
            if ($char1 === '$' && $char3 === '%' && ctype_digit($char2)) {
                return self::RESULT_PRINTF;
            }
        }

        // "%'.9d", "%'.9d", "%2$d, "%1$04d"
        /// todo?

        return null;
    }

}
