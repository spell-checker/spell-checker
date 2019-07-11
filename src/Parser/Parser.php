<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

interface Parser
{

    public const KEYWORD = 1;
    public const NAME = 2;
    public const STRING = 3;
    public const COMMENT = 4;

    /**
     * Parse words from text
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array;

}
