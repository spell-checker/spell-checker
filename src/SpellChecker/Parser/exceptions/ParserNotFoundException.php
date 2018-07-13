<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use function sprintf;

class ParserNotFoundException extends \Exception
{

    /** @var string */
    private $name;

    public function __construct(string $name, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Parser or ParserFactory with name "%s" not found.', $name), 0, $previous);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
