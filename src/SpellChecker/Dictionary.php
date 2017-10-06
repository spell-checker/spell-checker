<?php

namespace SpellChecker;

class Dictionary
{

    /** @var bool[] */
    private $wordIndex;

    public function __construct(string $fileName)
    {
        if (!is_file($fileName) || !is_readable($fileName)) {
            throw new \SpellChecker\DictionaryFileNotReadableException($fileName);
        }

        foreach (explode("\n", file_get_contents($fileName)) as $word) {
            $this->wordIndex[$word] = true;
        }
    }

    public function contains(string $word): bool
    {
        return isset($this->wordIndex[$word]);
    }

    public function info(): int
    {
        return count($this->wordIndex);
    }

}
