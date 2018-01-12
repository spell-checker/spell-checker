<?php declare(strict_types = 1);
// spell-check-ignore: ivxlcdm

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

/**
 * Heuristic to filter alphabetic and roman letter bullets
 */
class BulletsDetector implements \SpellChecker\Heuristic\Heuristic
{

    /** @var string[] */
    private $bullets;

    public function __construct()
    {
        $this->bullets = array_flip(range('a', 'z') + range('A', 'Z'));
    }

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        // a) b) c) / A) B) C)
        if ($string[$word->position + 1] === ')'
            && isset($this->bullets[$word->word])
            && ctype_space($string[$word->position - 1])
        ) {
            return true;
        }

        // i) iv) x) / I) IV) X)
        if ($string[$word->position + 1] === ')'
            && ctype_space($string[$word->position - 1])
            && preg_match('/[ivxlcdm]+/i', $word->word)
        ) {
            return true;
        }

        return false;
    }

}
