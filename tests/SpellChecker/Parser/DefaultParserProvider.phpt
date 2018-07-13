<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use Dogma\Tools\Configurator;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$config = new Configurator(
    ['parsers' => ['p', Configurator::MAP, 'file extension -> parser name', 'map']],
    ['parsers' => ['phpt' => 'php', 'md' => 'txt']]
);
$config->loadCliArguments();
$languageResolver = new LanguageResolver($config->parsers);
$plainTextParser = new PlainTextParser();
$provider = new DefaultParserProvider($plainTextParser, $languageResolver, $config);


$phpParser = $provider->getParser('php');
Assert::type(PhpParser::class, $phpParser);

$phptParser = $provider->getParser('phpt');
Assert::type(PhpParser::class, $phptParser);

$mdParser = $provider->getParser('md');
Assert::type(PlainTextParser::class, $mdParser);

/** @var \SpellChecker\Parser\UniversalParser $jsParser */
$jsParser = $provider->getParser('js');
Assert::type(UniversalParser::class, $jsParser);
Assert::equal($jsParser->getSettings(), UniversalParserSettings::javascript());

/** @var \SpellChecker\Parser\UniversalParser $tsParser */
$tsParser = $provider->getParser('ts');
Assert::type(UniversalParser::class, $tsParser);
Assert::equal($tsParser->getSettings(), UniversalParserSettings::javascript());


$config = new Configurator(
    ['parsers' => ['p', Configurator::MAP, 'file extension -> parser name', 'map']],
    ['parsers' => ['foo' => FooParser::class]]
);
$config->loadCliArguments();
$languageResolver = new LanguageResolver($config->parsers);
$plainTextParser = new PlainTextParser();
$provider = new DefaultParserProvider($plainTextParser, $languageResolver, $config);

$fooParser = $provider->getParser('foo');
Assert::type(FooParser::class, $fooParser);


$config = new Configurator(
    ['parsers' => ['p', Configurator::MAP, 'file extension -> parser name', 'map']],
    ['parsers' => ['foo' => FooParserFactory::class]]
);
$config->loadCliArguments();
$languageResolver = new LanguageResolver($config->parsers);
$plainTextParser = new PlainTextParser();
$provider = new DefaultParserProvider($plainTextParser, $languageResolver, $config);

$fooParser = $provider->getParser('foo');
Assert::type(FooParser::class, $fooParser);
