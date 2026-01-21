<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use Dogma\Str;
use SpellChecker\DictionaryFileNotReadableException;
use function explode;
use function file_get_contents;
use function in_array;
use function is_file;
use function is_int;
use function is_readable;
use function substr;
use function trim;

class Dictionary
{

    /** @var string[] */
    private array $files;

    /** @var string[] */
    private array $checkedFiles;

    /** @var int[]|string[] */
    private array $wordIndex;

    /** @var int[]|string[] */
    private array $strippedIndex;

    /**
     * @param string[] $files
     * @param string[] $checkedFiles
     */
    public function __construct(array $files, bool $diacritics = false, array $checkedFiles = [])
    {
        $this->files = $files;
        $this->checkedFiles = $checkedFiles;

        foreach ($files as $fileName) {
            if (!is_file($fileName) || !is_readable($fileName)) {
                throw new DictionaryFileNotReadableException($fileName);
            }

            $checked = in_array($fileName, $checkedFiles, true);

            $extension = substr($fileName, -3);
            if ($extension === 'dic') {
                // .dic -> .dia
                $diaName = substr($fileName, 0, -1) . 'a';
                $diaExists = in_array($diaName, $files, true);
                foreach (explode("\n", file_get_contents($fileName)) as $word) {
                    $word = trim($word);
                    if ($word === '' || $word[0] === '#') {
                        continue;
                    }
                    $this->wordIndex[$word] = $checked ? 0 : '_';
                    if ($diacritics && !$diaExists) {
                        $stripped = Str::removeDiacritics($word);
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

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function contains(string $word): bool
    {
        $found = isset($this->wordIndex[$word]);
        if ($found && is_int($this->wordIndex[$word])) {
            $this->wordIndex[$word]++;
        }

        return $found;
    }

    public function containsWithoutDiacritics(string $word): bool
    {
        $found = isset($this->strippedIndex[$word]);
        if ($found && is_int($this->strippedIndex[$word])) {
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
