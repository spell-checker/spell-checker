<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use Dogma\Tools\Configurator;
use function call_user_func;
use function class_exists;
use function is_subclass_of;
use function method_exists;
use function ucfirst;

class DefaultParserProvider implements ParserProvider
{

    /** @var \SpellChecker\Parser\PlainTextParser */
    private $plainTextParser;

    /** @var \SpellChecker\Parser\LanguageResolver */
    private $languageResolver;

    /** @var \Dogma\Tools\Configurator */
    private $config;

    /** @var \SpellChecker\Parser\Parser[] (string $extension => $parser) */
    private $parsers;

    public function __construct(
        PlainTextParser $plainTextParser,
        LanguageResolver $languageResolver,
        Configurator $config
    ) {
        $this->plainTextParser = $plainTextParser;
        $this->languageResolver = $languageResolver;
        $this->config = $config;
    }

    public function getParser(string $extension): Parser
    {
        if (isset($this->parsers[$extension])) {
            return $this->parsers[$extension];
        }

        $name = $this->languageResolver->getParserName($extension) ?? $extension;

        if (class_exists('SpellChecker\\Parser\\' . ucfirst($name) . 'Parser')) {
            $class = 'SpellChecker\\Parser\\' . ucfirst($name) . 'Parser';
        } elseif (class_exists($name)) {
            $class = $name;
        } elseif (method_exists(UniversalParserSettings::class, $name)) {
            $this->parsers[$extension] = new UniversalParser($this->plainTextParser, UniversalParserSettings::get($name));

            return $this->parsers[$extension];
        } elseif ($name === self::DEFAULT_PARSER) {
            $this->parsers[$extension] = $this->plainTextParser;

            return $this->parsers[$extension];
        } else {
            throw new ParserNotFoundException($name);
        }

        if (is_subclass_of($class, Parser::class)) {
            $this->parsers[$extension] = new $class($this->plainTextParser, $extension);
        } elseif (is_subclass_of($class, ParserFactory::class)) {
            $this->parsers[$extension] = call_user_func([$class, 'createParser'], $this->plainTextParser, $this->config, $extension);
        } else {
            throw new WrongParserClassException($name, $class);
        }

        return $this->parsers[$extension];
    }

    /**
     * @return \SpellChecker\Parser\Parser[]
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

}
