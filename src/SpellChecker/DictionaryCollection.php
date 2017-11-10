<?php declare(strict_types = 1);

namespace SpellChecker;

class DictionaryCollection
{

    /** @var string|null */
    private $baseDir;

    /** @var string[] */
    private $files;

    /** @var string[] */
    private $checked;

    /** @var \SpellChecker\Dictionary[] */
    private $dictionaries;

    /** @var string[] */
    private $diacriticDictionaries;

    /**
     * @param string[] $files
     * @param string[] $diacriticDictionaries
     * @param string[] $checked
     * @param string|null $baseDir
     */
    public function __construct(array $files, array $diacriticDictionaries, array $checked, ?string $baseDir = null)
    {
        $this->files = $files;
        $this->checked = $checked;
        $this->baseDir = $baseDir !== null ? trim($baseDir, '/') : null;
        $this->dictionaries = [];
        $this->diacriticDictionaries = $diacriticDictionaries;
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
                $this->createDictionary($dictionary);
            }

            if ($this->dictionaries[$dictionary]->contains($word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $word
     * @param string[] $dictionaries
     * @return bool
     */
    public function containsWithoutDiacritics(string $word, array $dictionaries): bool
    {
        $stripped = DiacriticsHelper::removeDiacritics($word);
        foreach ($dictionaries as $dictionary) {
            if (!in_array($dictionary, $this->diacriticDictionaries)) {
                continue;
            }
            if (!isset($this->dictionaries[$dictionary])) {
                $this->createDictionary($dictionary);
            }

            if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($stripped)) {
                return true;
            }
        }

        return false;
    }

    private function createDictionary(string $dictionary): void
    {
        if (!isset($this->files[$dictionary])) {
            throw new \SpellChecker\DictionaryNotDefinedException($dictionary);
        }
        $dictionaryPath = $this->baseDir !== null
            ? $this->baseDir . '/' . $this->files[$dictionary]
            : getcwd() . '/' . $this->files[$dictionary];

        $this->dictionaries[$dictionary] = new Dictionary(
            $dictionaryPath,
            in_array($dictionary, $this->diacriticDictionaries),
            in_array($dictionary, $this->checked)
        );
    }

    /**
     * @return \SpellChecker\Dictionary[]
     */
    public function getDictionaries(): array
    {
        return $this->dictionaries;
    }

}
