<?php declare(strict_types = 1);
// spell-check-ignore: px pc vh vw vmin vmax ic lh rlh dpi dppx dpcm

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

class CssUnitsDetector implements \SpellChecker\Heuristic\Heuristic
{

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        // color codes
        if (preg_match('/^[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $word->word)) {
            $char = $string[$word->position - 1];
            if ($char === '#') {
                return true;
            }
        }
        // length, resolution, angle, frequency and time units
        if (preg_match('/^-?[0-9]+(?:em|ex|px|pc|pt|mm|cm|in|q|ch|rem|vh|vw|vi|vb|vmin|vmax|ic|lh|rlh|cap|dpi|dppx|dpcm|deg|rad|grad|turn|Hz|kHz|s|ms)$/', $word->block ?? $word->word)) {
            return true;
        }

        return false;
    }

}
