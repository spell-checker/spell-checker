<?php declare(strict_types = 1);

namespace SpellChecker;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Dictionary\DictionaryResolver;
use SpellChecker\Parser\Parser;

class SpellChecker
{

    public const DEFAULT_MAX_ERRORS = 1000;

    public const DEFAULT_PARSER = '*';

    /** @var \SpellChecker\Parser\Parser[] */
    private $wordsParsers;

    /** @var \SpellChecker\Heuristic\Heuristic[] */
    private $heuristics;

    /** @var \SpellChecker\Dictionary\DictionaryResolver */
    private $resolver;

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    /** @var int */
    private $maxErrors;

    /** @var  string[][] */
    private $localIgnores;

    /** @var bool */
    private $checkLocalIgnores;

    /**
     * @param \SpellChecker\Parser\Parser[] $wordsParsers
     * @param \SpellChecker\Heuristic\Heuristic[] $heuristics
     * @param \SpellChecker\Dictionary\DictionaryResolver $resolver
     * @param \SpellChecker\Dictionary\DictionaryCollection $dictionaries
     * @param int $maxErrors
     * @param string[] $localIgnores
     * @param bool $checkLocalIgnores
     */
    public function __construct(
        array $wordsParsers,
        array $heuristics,
        DictionaryResolver $resolver,
        DictionaryCollection $dictionaries,
        int $maxErrors = self::DEFAULT_MAX_ERRORS,
        array $localIgnores = [],
        bool $checkLocalIgnores = false
    )
    {
        $this->wordsParsers = $wordsParsers;
        $this->heuristics = $heuristics;
        $this->resolver = $resolver;
        $this->dictionaries = $dictionaries;
        $this->maxErrors = $maxErrors;
        $this->setIgnores($localIgnores);
        $this->checkLocalIgnores = $checkLocalIgnores;
    }

    /**
     * @param string[] $patterns
     */
    private function setIgnores(array $patterns): void
    {
        foreach ($patterns as $pattern => $words) {
            $pattern = '/^' . str_replace(['.', '*', '?', '/'], ['\\.', '.*', '.', '\\/'], $pattern) . '$/';
            $this->localIgnores[$pattern] = array_unique(array_filter(explode(' ', $words)));
        }
    }

    /**
     * @param string[] $files
     * @param callable|null (string fileName: bool) $fileCallback
     * @return \SpellChecker\Result
     */
    public function checkFiles(array $files, ?callable $fileCallback = null): Result
    {
        $errors = [];
        $count = $prevCount = 0;
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $dictionaries = $this->resolver->getDictionariesForFileName($path);
            if ($dictionaries === []) {
                continue;
            }
            $fileErrors = $this->checkFile($path, $dictionaries, $fileCallback);
            if ($fileErrors !== []) {
                $errors[$path] = $fileErrors;
                $count += count($fileErrors);
            }
            if ($count >= $this->maxErrors) {
                $errors[$path] = array_slice($errors[$path], 0, $this->maxErrors - $prevCount);
                $count = $this->maxErrors;
                break;
            }
            $prevCount = $count;
        }

        return new Result($errors, $count);
    }

    /**
     * @param string $fileName
     * @param string[] $dictionaries
     * @param callable|null (string fileName: bool) $fileCallback
     * @return \SpellChecker\Word[]
     */
    private function checkFile(string $fileName, array $dictionaries, ?callable $fileCallback = null): array
    {
        if ($fileCallback !== null) {
            if (!$fileCallback($fileName)) {
                return [];
            }
        }

        $string = file_get_contents($fileName);
        $string = \Nette\Utils\Strings::normalize($string);

        $ignores = [];
        if ($this->localIgnores !== []) {
            foreach ($this->localIgnores as $pattern => $words) {
                if (preg_match($pattern, $fileName)) {
                    $ignores = $words;
                }
            }
        }
        if (preg_match_all('/spell-check-ignore: ([^\\n]+)\\n/', $string, $match)) {
        	foreach ($match[1] as $line) {
		        $commentIgnores = preg_split('/[\\s+,]+/', $line);
		        // may end with */ --> *} etc
		        if (!preg_match('/\\pL/u', end($commentIgnores))) {
			        array_pop($commentIgnores);
		        }
		        $ignores = array_merge($ignores, $commentIgnores);
	        }
        }

        $fileNameParts = explode('.', basename($fileName));
        $extension = end($fileNameParts);
        $parser = $this->wordsParsers[$extension] ?? $this->wordsParsers[self::DEFAULT_PARSER];

        return $this->checkString($string, $dictionaries, $ignores, $parser);
    }

    /**
     * @param string $string
     * @param string[] $dictionaries
     * @param string[] $ignores
     * @param \SpellChecker\Parser\Parser|null $parser
     * @return \SpellChecker\Word[]
     */
    public function checkString(string $string, array $dictionaries, array $ignores, ?Parser $parser = null): array
    {
        $parser = $parser ?? $this->wordsParsers[self::DEFAULT_PARSER];

        $errors = [];
        $string = preg_replace([
                '~data:image/(?:jpeg|png|gif);base64,([A-Za-z0-9/+]+)~',
                '~("[^\\\\]*)((?:\\\\n)+)([^"]*")~',
                '~("[^\\\\]*)((?:\\\\r)+)([^"]*")~',
                '~("[^\\\\]*)((?:\\\\t)+)([^"]*")~',
            ], ['', '$1↓$3', '$1⬇$3', '$1→$3'], $string
        );

        if ($ignores !== []) {
            $ignores = array_combine($ignores, array_fill(0, count($ignores), 0));
            foreach ($parser->parse($string) as $n => $word) {
                foreach ($this->heuristics as $heuristic) {
                    if ($heuristic->check($word, $string, $dictionaries)) {
                        continue 2;
                    }
                }
                if (isset($ignores[$word->word])) {
                    $ignores[$word->word]++;
                    continue;
                }

                if ($word->row === null) {
                    $word->row = RowHelper::getRowAtPosition($string, $word->position);
                }
                $errors[] = $word;
            }

            if ($this->checkLocalIgnores) {
                foreach ($ignores as $word => $count) {
                    if ($count < 2) {
                        if (!isset($ignoreRow)) {
                            preg_match('/spell-check-ignore: ([^\\n]+)\\n/', $string, $ignoreRow);
                        }
                        if (isset($ignoreRow[1]) && strpos($ignoreRow[1], $word) !== false) {
                            $position = strpos($string, $word);
                            $preceding = substr($string, 0, $position);
                            $rowNumber = strlen($preceding) - strlen(str_replace("\n", '', $preceding)) + 1;
                            $rowStart = strrpos(substr($string, 0, $position), "\n") + 1;
                            $rowEnd = $position + strpos(substr($string, $position), "\n");
                            $word = new Word($word, null, $position, $rowNumber);
                            $word->block = true;
                            $word->row = trim(substr($string, $rowStart, $rowEnd - $rowStart));
                            $errors[] = $word;
                        } elseif ($count === 0) {
                            $word = new Word($word, null, -1, -1);
                            $word->block = true;
                            $word->row = 'configuration';
                            $errors[] = $word;
                        }
                    }
                }
            }
        } else {
            // the same as previous, only without checking ignores
            foreach ($parser->parse($string) as $n => $word) {
                foreach ($this->heuristics as $heuristic) {
                    if ($heuristic->check($word, $string, $dictionaries)) {
                        continue 2;
                    }
                }

                if ($word->row === null) {
                    $word->row = RowHelper::getRowAtPosition($string, $word->position);
                }
                $errors[] = $word;
            }
        }

        return $errors;
    }

}
