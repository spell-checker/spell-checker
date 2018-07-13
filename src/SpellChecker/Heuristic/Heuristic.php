<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

interface Heuristic
{

    //public function __construct(DictionaryCollection $dictionaries);

    /**
     * Checks the given word.
     * Returns the name of rule or dictionary for which the word matches or null if it does not match.
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string;

}
