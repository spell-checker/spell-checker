<?php declare(strict_types = 1);

namespace SpellChecker;

class SpellChecker
{

    /** @var \SpellChecker\WordsParser */
    private $wordsParser;

    /** @var \SpellChecker\GarbageDetector */
    private $garbageDetector;

    /** @var \SpellChecker\DictionaryResolver */
    private $resolver;

    /** @var \SpellChecker\DictionaryCollection */
    private $dictionaries;

    public function __construct(
        WordsParser $wordsParser,
        GarbageDetector $garbageDetector,
        DictionaryResolver $resolver,
        DictionaryCollection $dictionaries
    )
    {
        $this->wordsParser = $wordsParser;
        $this->garbageDetector = $garbageDetector;
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
        ///
        $string = file_get_contents($fileName);
        $string = \Nette\Utils\Strings::normalize($string);
        ///

        return $this->checkString($string, $dictionaries);
    }

    /**
     * @param string $string
     * @param string[] $dictionaries
     * @return \SpellChecker\Word[]
     */
    public function checkString(string $string, array $dictionaries): array
    {
        $errors = [];
        foreach ($this->wordsParser->parse($string) as $word) {
            if ($this->dictionaries->contains($word->word, $dictionaries)) {
                continue;
            }
            if ($this->dictionaries->contains(mb_strtolower($word->word), $dictionaries)) {
                continue;
            }
            if ($word->block !== null && $this->dictionaries->contains($word->block, $dictionaries)) {
                continue;
            }

            $trimmed = $this->trimNumbersFromRight($word->word);
            if ($trimmed !== null) {
                if ($this->dictionaries->contains($trimmed, $dictionaries)) {
                    continue;
                }
                if ($this->dictionaries->contains(mb_strtolower($trimmed), $dictionaries)) {
                    continue;
                }
            }
            if ($word->block !== null && $this->garbageDetector->looksLikeGarbage($word->block)) {
                continue;
            }
            if ($this->garbageDetector->looksLikeGarbage($word->word)) {
                continue;
            }

            $rowStart = (int) strrpos($string, "\n", $word->position - strlen($string));
            $rowEnd = strpos($string, "\n", $rowStart + 1) ?: strlen($string);
            $row = trim(substr($string, $rowStart, $rowEnd - $rowStart));
            if (strlen($row) > 300) {
                $row = substr($row, 0, 300) . '…';
            }
            $word->row = $row;
            $word->rowNumber = $word->position - strlen(str_replace("\n", '', substr($string, 0, $word->position))) + 1;

            $errors[] = $word;
        }

        return $errors;
    }

    private function trimNumbersFromRight(string $word): ?string
    {
        if (preg_match('/[0-9]+$/', $word, $match)) {
            return substr($word, 0, -strlen($match[0]));
        } else {
            return null;
        }
    }

}
