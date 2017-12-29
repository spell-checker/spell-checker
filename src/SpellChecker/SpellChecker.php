<?php declare(strict_types = 1);

namespace SpellChecker;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Dictionary\DictionaryResolver;

class SpellChecker
{

    /** @var \SpellChecker\WordsParser */
    private $wordsParser;

    /** @var \SpellChecker\Heuristic\Heuristic[] */
    private $heuristics;

    /** @var \SpellChecker\Dictionary\DictionaryResolver */
    private $resolver;

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    /**
     * @param \SpellChecker\WordsParser $wordsParser
     * @param \SpellChecker\Heuristic\Heuristic[] $heuristics
     * @param \SpellChecker\Dictionary\DictionaryResolver $resolver
     * @param \SpellChecker\Dictionary\DictionaryCollection $dictionaries
     */
    public function __construct(
        WordsParser $wordsParser,
        array $heuristics,
        DictionaryResolver $resolver,
        DictionaryCollection $dictionaries
    )
    {
        $this->wordsParser = $wordsParser;
        $this->heuristics = $heuristics;
        $this->resolver = $resolver;
        $this->dictionaries = $dictionaries;
    }

    /**
     * @param string[] $files
     * @param callable|null (string fileName: bool) $fileCallback
     * @return \SpellChecker\Result
     */
    public function checkFiles(array $files, ?callable $fileCallback = null): Result
    {
        $errors = [];
        $count = 0;
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
        if (preg_match('/spell-check-ignore: ([^\\n]+)\\n/', $string, $match)) {
            $ignores = explode(' ', $match[1]);
        }

        return $this->checkString($string, $dictionaries, $ignores);
    }

    /**
     * @param string $string
     * @param string[] $dictionaries
     * @param string[] $ignores
     * @return \SpellChecker\Word[]
     */
    public function checkString(string $string, array $dictionaries, array $ignores): array
    {
        $errors = [];
        $string = preg_replace([
                '~data:image/(?:jpeg|png|gif);base64,([A-Za-z0-9/+]+)~',
                '~("[^\\\\]*)((?:\\\\n)+)([^"]*")~',
                '~("[^\\\\]*)((?:\\\\r)+)([^"]*")~',
                '~("[^\\\\]*)((?:\\\\t)+)([^"]*")~',
            ], ['', '$1↓$3', '$1⬇$3', '$1→$3'], $string
        );

        if ($ignores !== []) {
            $ignores = array_flip($ignores);
            foreach ($this->wordsParser->parse($string) as $n => $word) {
                if (isset($ignores[$word->word])) {
                    continue;
                }

                foreach ($this->heuristics as $heuristic) {
                    if ($heuristic->check($word, $string, $dictionaries)) {
                        continue 2;
                    }
                }

                $word->row = trim(substr($string, $word->rowStart, $word->rowEnd - $word->rowStart));
                $errors[] = $word;
            }
        } else {
            // the same as previous, only without checking ignores
            foreach ($this->wordsParser->parse($string) as $n => $word) {
                foreach ($this->heuristics as $heuristic) {
                    if ($heuristic->check($word, $string, $dictionaries)) {
                        continue 2;
                    }
                }

                $word->row = trim(substr($string, $word->rowStart, $word->rowEnd - $word->rowStart));
                $errors[] = $word;
            }
        }

        return $errors;
    }

}
