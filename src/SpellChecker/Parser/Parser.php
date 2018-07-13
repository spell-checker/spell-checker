<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

interface Parser
{

    //public function __construct(PlainTextParser $plainTextParser);

    /**
     * Parse words from text
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array;

}
