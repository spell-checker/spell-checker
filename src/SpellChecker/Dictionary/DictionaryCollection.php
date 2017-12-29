<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use SpellChecker\DiacriticsHelper;
use Symfony\Component\Finder\Finder;

class DictionaryCollection
{

    /** @var string|null */
    private $baseDir;

    /** @var string[] */
    private $directories;

    /** @var string[] */
    private $files;

    /** @var string[] */
    private $checkedFiles;

    /** @var \SpellChecker\Dictionary\Dictionary[] */
    private $dictionaries;

    /** @var string[] */
    private $diacriticDictionaries;

    /**
     * @param string[] $directories
     * @param string[] $diacriticDictionaries
     * @param string[] $checkedFiles
     * @param string|null $baseDir
     */
    public function __construct(array $directories, array $diacriticDictionaries, array $checkedFiles, ?string $baseDir = null)
    {
        $this->directories = $directories;
        $this->checkedFiles = $checkedFiles;
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
        if ($this->files === null) {
            $this->findDictionaryFiles();
        }

        $files = array_filter($this->files, function (string $filePath) use ($dictionary): bool {
            $fileName = basename($filePath);
            return strpos($fileName, $dictionary) === 0;
        });
        $checkedFiles = array_filter($this->files, function (string $filePath): bool {
            $fileName = basename($filePath);
            return in_array($fileName, $this->checkedFiles);
        });
        if ($files === []) {
            throw new \SpellChecker\NoDictionaryFileFoundException($dictionary);
        }

        $this->dictionaries[$dictionary] = new Dictionary(
            $files,
            in_array($dictionary, $this->diacriticDictionaries),
            $checkedFiles
        );
    }

    private function findDictionaryFiles(): void
    {
        $directories = array_map(function (string $directory): string {
            return $this->baseDir !== null
                ? $this->baseDir . '/' . $directory
                : getcwd() . '/' . $directory;
        }, $this->directories);

        $finder = new Finder();
        $finder->files();
        $finder->name('*.dic');
        $finder->in($directories);
        $finder->ignoreDotFiles(true);
        $finder->ignoreVCS(true);
        $this->files = array_keys(iterator_to_array($finder->getIterator()));
    }

    /**
     * @return \SpellChecker\Dictionary\Dictionary[]
     */
    public function getDictionaries(): array
    {
        return $this->dictionaries;
    }

}
