<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

interface ParserProvider
{

    public const DEFAULT_PARSER = 'txt';

    //public function __construct(PlainTextParser $plainTextParser, Configurator $config);

    public function getParser(string $extension): Parser;

}
