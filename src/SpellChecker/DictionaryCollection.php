<?php

namespace SpellChecker;

class DictionaryCollection
{

    /** @var string[] */
    private $files;

    /** @var \SpellChecker\Dictionary[] */
    private $dictionaries;

    /**
     * @param string[] $files
     */
    public function __construct(array $files)
    {
        $this->files = $files;
        $this->dictionaries = [];
    }

    /**
     * @param string $word
     * @param string[] $dictionaries
     * @return bool
     */
    public function contains(string $word, array $dictionaries): bool
    {
        foreach ($dictionaries as $dictionary) {
            if (!isset($this->dictionaries[$dictionary])) {
                if (!isset($this->files[$dictionary])) {
                    throw new \SpellChecker\DictionaryNotDefinedException($dictionary);
                }
                $this->dictionaries[$dictionary] = new Dictionary($this->files[$dictionary]);
            }

            if ($this->dictionaries[$dictionary]->contains($word)) {
                return true;
            }
        }

        return false;
    }

    public function info(): string
    {
        $info = '';
        foreach ($this->dictionaries as $name => $dictionary) {
            $info .= $name . ' (' . $dictionary->info() . '), ';
        }

        return $info;
    }

}
