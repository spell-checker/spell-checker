<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use function sprintf;

class WrongParserClassException extends \Exception
{

    /** @var string */
    private $name;

    /** @var string */
    private $className;

    public function __construct(string $name, string $className, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Class "%s" for parser name "%s" must implement either Parser or ParserFactory interface.', $className, $name), 0, $previous);

        $this->name = $name;
        $this->className = $className;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

}
