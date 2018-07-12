<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function end;
use function explode;
use function preg_match;
use function str_replace;
use function strpos;

class DictionaryResolver
{

    private const SKIP_FILE_MARKER = 'skip';

    /** @var string[] */
    private $dictionaries;

    /** @var string[][] */
    private $byFileName = [];

    /** @var string[][] */
    private $byExtensions;

    /**
     * @param string[] $dictionaries
     * @param string[] $dictionariesByFileName
     * @param string[] $dictionariesByExtension
     */
    public function __construct(array $dictionaries, array $dictionariesByFileName, array $dictionariesByExtension)
    {
        $this->dictionaries = $dictionaries;
        $this->setPatterns($dictionariesByFileName);
        $this->byExtensions = $this->sanitize($dictionariesByExtension);
    }

    /**
     * @param string[] $values
     * @return string[][]
     */
    private function sanitize(array $values): array
    {
        return array_map(function (string $value) {
            return array_unique(array_filter(explode(' ', $value)));
        }, $values);
    }

    /**
     * @param string[] $patterns
     */
    private function setPatterns(array $patterns): void
    {
        foreach ($patterns as $pattern => $dictionaries) {
            $pattern = '/^' . str_replace(['.', '*', '?', '/'], ['\\.', '.*', '.', '\\/'], $pattern) . '$/';
            $this->byFileName[$pattern] = array_unique(array_filter(explode(' ', $dictionaries)));
        }
    }

    /**
     * @param string $fileName
     * @return string[]
     */
    public function getDictionariesForFileName(string $fileName): array
    {
        $dictionaries = $this->getDictionariesByFileNamePattern($fileName);
        if ($dictionaries === null) {
            return [];
        }

        $dictionaries = array_merge($this->dictionaries, $dictionaries);

        if (strpos($fileName, '.') === false) {
            return $dictionaries;
        }

        $parts = explode('.', $fileName);
        $extension = end($parts);
        if (isset($this->byExtensions[$extension])) {
            $dictionaries = array_merge($dictionaries, $this->byExtensions[$extension]);
        }

        return $dictionaries;
    }

    /**
     * @param string $fileName
     * @return string[]|null
     */
    private function getDictionariesByFileNamePattern(string $fileName): ?array
    {
        foreach ($this->byFileName as $pattern => $dictionaries) {
            if (preg_match($pattern, $fileName)) {
                if ($dictionaries === [self::SKIP_FILE_MARKER]) {
                    return null;
                } else {
                    return $dictionaries;
                }
            }
        }

        return [];
    }

}
