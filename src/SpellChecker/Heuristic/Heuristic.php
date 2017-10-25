<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

interface Heuristic
{

    public function check(Word $word, string &$string): bool;

}
