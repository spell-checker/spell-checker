<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Console;
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

    /** @var \Dogma\Tools\Console|null */
    private $console;

    /**
     * @param string[] $directories
     * @param string[] $diacriticDictionaries
     * @param string[] $checkedFiles
     * @param string|null $baseDir
     */
    public function __construct(
        array $directories,
        array $diacriticDictionaries,
        array $checkedFiles,
        ?string $baseDir = null,
        ?Console $console = null
    )
    {
        $this->directories = $directories;
        $this->checkedFiles = $checkedFiles;
        $this->baseDir = $baseDir !== null ? trim($baseDir, '/') : null;
        $this->dictionaries = [];
        $this->diacriticDictionaries = $diacriticDictionaries;
        $this->console = $console ?? new Console();
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
        $this->console->debugWrite(C::gray('['), C::yellow($dictionary));

        if ($this->files === null) {
            $this->findDictionaryFiles();
        }

        $files = array_filter($this->files, function (string $filePath) use ($dictionary): bool {
            $fileName = basename($filePath);
            $next = substr($fileName, strlen($dictionary), 1);
            return strpos($fileName, $dictionary) === 0 && ($next === '-' || $next === '.');
        });
        $checkedFiles = array_filter($this->files, function (string $filePath): bool {
            $fileName = basename($filePath);
            return in_array($fileName, $this->checkedFiles);
        });
        if ($files === []) {
            throw new \SpellChecker\NoDictionaryFileFoundException($dictionary);
        }

        $this->console->debugWrite(C::gray(': ' . implode(' ', array_map(function (string $filePath): string {
            return basename($filePath);
        }, $files))));

        $startTime = microtime(true);
        $this->dictionaries[$dictionary] = new Dictionary(
            $files,
            in_array($dictionary, $this->diacriticDictionaries),
            $checkedFiles
        );

        $totalTime = microtime(true) - $startTime;
        $this->console->debugWrite(' ', number_format($totalTime, 3), 's', C::gray(']'));
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
        $finder->name('*.dia');
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
