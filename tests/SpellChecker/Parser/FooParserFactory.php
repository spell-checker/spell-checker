<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use Dogma\Tools\Configurator;

class FooParserFactory implements ParserFactory
{

    public static function createParser(PlainTextParser $plainTextParser, Configurator $config): Parser
    {
        return new FooParser();
    }

}
