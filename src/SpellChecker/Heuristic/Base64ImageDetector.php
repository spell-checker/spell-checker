<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\RowHelper;
use SpellChecker\Word;
use function preg_match_all;
use function strrpos;

class Base64ImageDetector implements \SpellChecker\Heuristic\Heuristic
{

    public const RESULT_IMAGE = 'image';

    /**
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        if ($word->row === null) {
            $word->row = RowHelper::getRowAtPosition($string, $word->position);
        }

        // data:image/jpeg;base64,
        if (preg_match_all('~data:image/(?:jpeg|png|gif);base64,([A-Za-z0-9/+]+)~', $word->row, $matches)) {
            foreach ($matches[1] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    return self::RESULT_IMAGE;
                }
            }
        }

        return null;
    }

}
