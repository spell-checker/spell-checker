<?php declare(strict_types = 1);
// spell-check-ignore: ivxlcdm

namespace SpellChecker\Heuristic;

use SpellChecker\Word;
use function array_flip;
use function ctype_space;
use function preg_match;
use function range;

/**
 * Heuristic to filter alphabetic and roman letter bullets
 */
class BulletsDetector implements \SpellChecker\Heuristic\Heuristic
{

    public const RESULT_LATIN = 'latin';
    public const RESULT_ROMAN = 'roman';

    /** @var string[] */
    private $bullets;

    public function __construct()
    {
        $this->bullets = array_flip(range('a', 'z') + range('A', 'Z'));
    }

    /**
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        // a) b) c) / A) B) C)
        if ($string[$word->position + 1] === ')'
            && isset($this->bullets[$word->word])
            && ctype_space($string[$word->position - 1])
        ) {
            return self::RESULT_LATIN;
        }

        // i) iv) x) / I) IV) X)
        if ($string[$word->position + 1] === ')'
            && ctype_space($string[$word->position - 1])
            && preg_match('/[ivxlcdm]+/i', $word->word)
        ) {
            return self::RESULT_ROMAN;
        }

        return null;
    }

}
