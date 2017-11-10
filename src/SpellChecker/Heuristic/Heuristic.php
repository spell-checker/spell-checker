<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

interface Heuristic
{

    /**
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return bool
     */
    public function check(Word $word, string &$string, array $dictionaries): bool;

}
