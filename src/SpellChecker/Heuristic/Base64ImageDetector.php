<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

class Base64ImageDetector implements \SpellChecker\Heuristic\Heuristic
{

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        $row = substr($string, $word->rowStart, $word->rowEnd - $word->rowStart);

        // data:image/jpeg;base64,
        if (preg_match_all('~data:image/(?:jpeg|png|gif);base64,([A-Za-z0-9/+]+)~', $row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

}
