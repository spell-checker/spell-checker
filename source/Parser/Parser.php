<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use SpellChecker\Word;

interface Parser
{

    public const int KEYWORD = 1;
    public const int NAME = 2;
    public const int STRING = 3;
    public const int COMMENT = 4;

    /**
     * Parse words from text
     * @return Word[]
     */
    public function parse(string $string): array;

}
