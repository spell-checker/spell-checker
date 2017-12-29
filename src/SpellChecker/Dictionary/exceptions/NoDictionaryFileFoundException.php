<?php declare(strict_types = 1);

namespace SpellChecker;

class NoDictionaryFileFoundException extends \Exception
{

    /** @var string */
    private $dictionary;

    public function __construct(string $dictionary, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('No dictionary files found for dictionary name "%s".', $dictionary), 0, $previous);

        $this->dictionary = $dictionary;
    }

    public function getDictionary(): string
    {
        return $this->dictionary;
    }

}
