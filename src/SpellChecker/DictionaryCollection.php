<?php declare(strict_types = 1);

namespace SpellChecker;

class DictionaryCollection
{

    /** @var string|null */
    private $baseDir;

    /** @var string[] */
    private $files;

    /** @var \SpellChecker\Dictionary[] */
    private $dictionaries;

    /**
     * @param string[] $files
     * @param string|null $baseDir
     */
    public function __construct(array $files, ?string $baseDir = null)
    {
        $this->files = $files;
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
                if (!isset($this->files[$dictionary])) {
                    throw new \SpellChecker\DictionaryNotDefinedException($dictionary);
                }
                $dictionaryPath = $this->baseDir !== null
                    ? $this->baseDir . '/' . $this->files[$dictionary]
                    : getcwd() . '/' . $this->files[$dictionary];
                $this->dictionaries[$dictionary] = new Dictionary($dictionaryPath);
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
