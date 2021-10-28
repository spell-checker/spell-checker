<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use SpellChecker\Word;

interface Parser
{

    public const KEYWORD = 1;
    public const NAME = 2;
    public const STRING = 3;
    public const COMMENT = 4;

    /**
     * Parse words from text
     * @return Word[]
     */
    public function parse(string $string): array;

}
