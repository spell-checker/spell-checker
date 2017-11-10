<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

class PrintfDetector implements \SpellChecker\Heuristic\Heuristic
{

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        // "%d"
        if (preg_match('/^[bcdeEfFgGosuxX]/', $word->word)) {
            $char = $string[$word->position - 1];
            if ($char === '%') {
                return true;
            }
        }

        // "%'.9d", "%'.9d", "%2$d, "%1$04d"
        /// todo?

        return false;
    }

}
