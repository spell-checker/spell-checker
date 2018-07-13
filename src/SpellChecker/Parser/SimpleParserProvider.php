<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class SimpleParserProvider implements ParserProvider
{

    /** @var \SpellChecker\Parser\Parser[] */
    private $parsers;

    /**
     * @param \SpellChecker\Parser\Parser[] $parsers
     */
    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }

    public function getParser(string $extension): Parser
    {
        if (!isset($this->parsers[$extension])) {
            throw new ParserNotFoundException($extension);
        }

        return $this->parsers[$extension];
    }

}
