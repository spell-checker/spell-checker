<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Console;
use Nette\Utils\Strings;
use SpellChecker\DiacriticsHelper;
use SpellChecker\Heuristic\DictionarySearch;
use Symfony\Component\Finder\Finder;
use function array_filter;
use function array_keys;
use function array_map;
use function basename;
use function explode;
use function getcwd;
use function implode;
use function in_array;
use function iterator_to_array;
use function mb_strtolower;
use function memory_get_usage;
use function microtime;
use function number_format;
use function round;
use function strlen;
use function strpos;
use function substr;
use function trim;

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
     * @param \Dogma\Tools\Console|null $console
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
     * @param string[] $dictionaries
     * @param string $word
     * @param string|null $context
     * @param int $flags
     * @return bool
     */
    public function contains(array $dictionaries, string $word, ?string $context = null, int $flags = 0): bool
    {
        $dictionaries = $this->filterDictionaries($dictionaries, $context);

        $stripped = null;
        if ($flags & DictionarySearch::TRY_WITHOUT_DIACRITICS) {
            $stripped = DiacriticsHelper::removeDiacritics($word);
        }
        foreach ($dictionaries as $dictionary) {
            $forceWithoutDiacritics = false;
            if ($dictionary[0] === '*') {
                $forceWithoutDiacritics = true;
                $dictionary = substr($dictionary, 1);
            }

            if (!isset($this->dictionaries[$dictionary])) {
                $this->createDictionary($dictionary);
            }

            if ($forceWithoutDiacritics) {
                if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($word)) {
                    return true;
                }
                if ($flags & DictionarySearch::TRY_LOWERCASE) {
                    $lower = mb_strtolower($word);
                    if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($lower)) {
                        return true;
                    }
                }
                if ($flags & DictionarySearch::TRY_CAPITALIZED) {
                    $capitalized = Strings::firstUpper(mb_strtolower($word));
                    if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($capitalized)) {
                        return true;
                    }
                }
            } else {
                if ($this->dictionaries[$dictionary]->contains($word)) {
                    return true;
                }
                if ($flags & DictionarySearch::TRY_LOWERCASE) {
                    $lower = mb_strtolower($word);
                    if ($this->dictionaries[$dictionary]->contains($lower)) {
                        return true;
                    }
                }
                if ($flags & DictionarySearch::TRY_CAPITALIZED) {
                    $capitalized = Strings::firstUpper(mb_strtolower($word));
                    if ($this->dictionaries[$dictionary]->contains($capitalized)) {
                        return true;
                    }
                }
            }

            if ($flags & DictionarySearch::TRY_WITHOUT_DIACRITICS) {
                if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($stripped)) {
                    return true;
                }
                if ($flags & DictionarySearch::TRY_LOWERCASE) {
                    $lower = mb_strtolower($stripped);
                    if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($lower)) {
                        return true;
                    }
                }
                if ($flags & DictionarySearch::TRY_CAPITALIZED) {
                    $capitalized = Strings::firstUpper(mb_strtolower($stripped));
                    if ($this->dictionaries[$dictionary]->containsWithoutDiacritics($capitalized)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string[] $dictionaries
     * @param string $context
     * @return string[]
     */
    private function filterDictionaries(array $dictionaries, ?string $context): array
    {
        $result = [];
        foreach ($dictionaries as $dictionary) {
            [$dic, $dicContext] = explode('/', $dictionary . '/');
            if ($dicContext === '' || $dicContext === $context) {
                $result[] = $dic;
            }
        }

        return $result;
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
        $startMemory = memory_get_usage(true);
        $this->dictionaries[$dictionary] = new Dictionary(
            $files,
            in_array($dictionary, $this->diacriticDictionaries),
            $checkedFiles
        );

        $totalTime = number_format(microtime(true) - $startTime, 3);
        $consumedMemory = (string) round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 0);
        $this->console->debugWrite(' ', $totalTime, 's ', $consumedMemory, 'MB', C::gray(']'));
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
