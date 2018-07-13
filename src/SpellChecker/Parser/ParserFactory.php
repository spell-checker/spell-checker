<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use Dogma\Tools\Configurator;

interface ParserFactory
{

    public static function createParser(PlainTextParser $plainTextParser, Configurator $config): Parser;

}
