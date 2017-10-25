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

    /**
     * @param string[] $files
     * @param string[] $checked
     * @param string|null $baseDir
     */
    public function __construct(array $files, array $checked, ?string $baseDir = null)
    {
        $this->files = $files;
        $this->checked = $checked;
        $this->baseDir = $baseDir !== null ? trim($baseDir, '/') : null;
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
                $this->createDictionary($dictionary);
            }

            if ($this->dictionaries[$dictionary]->contains($word)) {
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

        $this->dictionaries[$dictionary] = new Dictionary($dictionaryPath, in_array($dictionary, $this->checked));
    }

    /**
     * @return \SpellChecker\Dictionary[]
     */
    public function getDictionaries(): array
    {
        return $this->dictionaries;
    }

}
