<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use SpellChecker\DiacriticsHelper;

class Dictionary
{

    /** @var string[] */
    private $files;

    /** @var string[] */
    private $checkedFiles;

    /** @var int[]|string[] */
    private $wordIndex;

    /** @var int[]|string[] */
    private $strippedIndex;

    public function __construct(array $files, bool $diacritics = false, array $checkedFiles = [])
    {
        $this->files = $files;
        $this->checkedFiles = $checkedFiles;

        foreach ($files as $fileName) {
            if (!is_file($fileName) || !is_readable($fileName)) {
                throw new \SpellChecker\DictionaryFileNotReadableException($fileName);
            }

            $checked = in_array($fileName, $checkedFiles);

            $extension = substr($fileName, -3);
            if ($extension === 'dic') {
                // .dic -> .dia
                $diaName = substr($fileName, 0, -1) . 'a';
                $diaExists = array_search($diaName, $files, true) !== false;
                foreach (explode("\n", file_get_contents($fileName)) as $word) {
                    $word = trim($word);
                    if ($word === '' || $word[0] === '#') {
                        continue;
                    }
                    $this->wordIndex[$word] = $checked ? 0 : '_';
                    if ($diacritics && !$diaExists) {
                        $stripped = DiacriticsHelper::removeDiacritics($word);
                        if ($stripped !== $word) {
                            $this->strippedIndex[$stripped] = $word;
                        }
                    }
                }
            } elseif ($extension === 'dia' && $diacritics) {
                foreach (explode("\n", file_get_contents($fileName)) as $word) {
                    $word = trim($word);
                    if ($word === '' || $word[0] === '#') {
                        continue;
                    }
                    $this->strippedIndex[$word] = $checked ? 0 : '_';
                }
            }
        }
    }

    public function contains(string $word): bool
    {
        $found = isset($this->wordIndex[$word]);
        if ($found) {
            $this->wordIndex[$word]++;
        }

        return $found;
    }

    public function containsWithoutDiacritics(string $word): bool
    {
        $found = isset($this->strippedIndex[$word]);
        if ($found) {
            $this->strippedIndex[$word]++;
        }

        return $found;
    }

    public function isChecked(): bool
    {
        return $this->checkedFiles !== [];
    }

    /**
     * @return string[]
     */
    public function getUnusedWords(): array
    {
        $words = [];
        foreach ($this->wordIndex as $word => $count) {
            if ($count === 0) {
                $words[] = $word;
            }
        }

        return $words;
    }

}
