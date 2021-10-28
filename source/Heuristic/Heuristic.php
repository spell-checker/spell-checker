<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

interface Heuristic
{

    /**
     * Checks the given word.
     * Returns the name of rule or dictionary for which the word matches or null if it does not match.
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string;

}
