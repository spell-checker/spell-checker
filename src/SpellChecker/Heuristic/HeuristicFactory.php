<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Dictionary\DictionaryCollection;

interface HeuristicFactory
{

    public static function createHeuristic(DictionaryCollection $dictionaries): Heuristic;

}
