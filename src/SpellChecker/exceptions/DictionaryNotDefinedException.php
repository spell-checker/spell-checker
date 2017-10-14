<?php declare(strict_types = 1);

namespace SpellChecker;

class DictionaryNotDefinedException extends \Exception
{

    /** @var string */
    private $dictionary;

    public function __construct(string $dictionary, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Dictionary with name "%s" is not defined in your configuration.', $dictionary), 0, $previous);

        $this->dictionary = $dictionary;
    }

    public function getDictionary(): string
    {
        return $this->dictionary;
    }

}
