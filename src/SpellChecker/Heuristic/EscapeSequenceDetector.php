<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

/**
 * Detects words, that are probably a string escape sequence or HTML entity
 */
class EscapeSequenceDetector implements \SpellChecker\Heuristic\Heuristic
{

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        // "\s"
        if (preg_match('/^[aefnpPrRtdDhHsSvVwWbBAzZG]/', $word->word)) {
            if ($string[$word->position - 1] === '\\') {
                return true;
            }
        }

        // hexadecimal HTML entity &#xeabb;
        if (preg_match('/^x[a-f0-9]{4}/', $word->word)) {
            if ($string[$word->position - 1] === '#' && $string[$word->position - 2] === '&') {
                return true;
            }
        }

        return false;
    }

}
