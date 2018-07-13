<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class FooParser implements Parser
{

    /**
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        return [];
    }

}
