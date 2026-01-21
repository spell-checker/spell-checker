<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Application\Colors as C;
use Dogma\Application\Console;
use SpellChecker\Dictionary\DictionaryResolver;
use function array_map;
use function array_unique;
use function arsort;
use function asort;
use function count;
use function implode;
use function mb_strlen;
use function mb_substr;
use function sort;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;
use function trim;
use function uasort;

class ResultFormatter
{

    private DictionaryResolver $dictionaryResolver;

    private ?string $baseDir;

    public function __construct(
        DictionaryResolver $dictionaryResolver,
        ?string $baseDir = null,
    )
    {
        $this->dictionaryResolver = $dictionaryResolver;
        $this->baseDir = $baseDir;
    }

    public function summarize(Result $result): string
    {
        if (!$result->errorsFound()) {
            return C::lgreen('No spelling errors found.');
        } else {
            return C::white(sprintf(
                'Found %d %s in %d %s.',
                $result->getErrorsCount(),
                $result->getErrorsCount() > 1 ? 'errors' : 'error',
                $result->getFilesCount(),
                $result->getFilesCount() > 1 ? 'files' : 'file'
            ), C::RED);
        }
    }

    /**
     * @param string[] $files
     * @return string
     */
    public function formatFilesList(array $files): string
    {
        $output = '';
        foreach ($files as $fileName) {
            $output .= C::lcyan($this->stripBaseDir($fileName)) . "\n";
        }

        return $output;
    }

    public function formatTopWords(Result $result): string
    {
        $words = [];
        foreach ($result->getErrors() as $fileErrors) {
            foreach ($fileErrors as $error) {
                $word = $error->word;
                if (isset($words[$word])) {
                    $words[$word]++;
                } else {
                    $words[$word] = 1;
                }
            }
        }
        asort($words);
        $output = '';
        foreach ($words as $word => $count) {
            if ($count === 1) {
                continue;
            }
            $output .= C::gray('- found "') . $word . C::gray('" ') . $count . C::gray(' times') . "\n";
        }
        return $output;
    }

    public function formatTopWordsByContext(Result $result): string
    {
        $contexts = [];
        foreach ($result->getErrors() as $fileName => $fileErrors) {
            $context = implode('-', $this->dictionaryResolver->getDictionariesForFileName($fileName));
            foreach ($fileErrors as $error) {
                $word = $error->word;
                if (isset($contexts[$context][$word])) {
                    $contexts[$context][$word]++;
                } else {
                    $contexts[$context][$word] = 1;
                }
            }
        }
        uasort($contexts, static function (array $words1, array $words2) {
            return count($words1) <=> count($words2);
        });
        foreach ($contexts as &$words) {
            arsort($words);
        }
        $output = '';
        foreach ($contexts as $context => $words) {
            $output .= C::lcyan($context) . "\n";
            foreach ($words as $word => $count) {
                if ($count < 10) {
                    continue;
                }
                $output .= C::gray('- found "') . $word . C::gray('" ') . $count . C::gray(' times') . "\n";
            }
        }
        return $output;
    }

    public function formatTopBlocksByDictionaries(Result $result): string
    {
        $contexts = [];
        foreach ($result->getErrors() as $fileName => $fileErrors) {
            $context = implode('-', $this->dictionaryResolver->getDictionariesForFileName($fileName));
            foreach ($fileErrors as $error) {
                $word = $error->block ?? $error->word;
                if (isset($contexts[$context][$word])) {
                    $contexts[$context][$word]++;
                } else {
                    $contexts[$context][$word] = 1;
                }
            }
        }
        uasort($contexts, static function (array $words1, array $words2) {
            return count($words1) <=> count($words2);
        });
        foreach ($contexts as &$words) {
            arsort($words);
        }
        $output = '';
        foreach ($contexts as $context => $words) {
            $output .= C::lcyan($context) . "\n";
            foreach ($words as $word => $count) {
                $output .= C::gray('- found "') . $word . C::gray('" ') . $count . C::gray(' times') . "\n";
            }
        }
        return $output;
    }

    public function formatErrors(Result $result): string
    {
        $maxWidth = Console::getTerminalWidth();
        $output = '';
        foreach ($result->getErrors() as $fileName => $errors) {
            $output .= $this->formatFileErrors($fileName, $errors, $maxWidth);
        }

        return $output;
    }

    /**
     * @param Word[] $errors
     * @return string
     */
    public function formatFileErrors(string $fileName, array $errors, int $maxWidth): string
    {
        $head = '' . C::lcyan($this->stripBaseDir($fileName)) . C::gray(' (')
            . implode(', ', $this->dictionaryResolver->getDictionariesForFileName($fileName)) . C::gray("):\n");

        $foot = '';
        $words = [];
        foreach ($errors as $word) {
            if ($word->block !== true) {
                $words[] = $word->word;
            }
            $row = trim($word->row);
            $padding = $word->block === true ? 35 : 27;
            if ($word->context !== null) {
                $padding += 3 + strlen($word->context);
            }
            $width = mb_strlen($word->word . $row . $word->rowNumber) + $padding;
            if ($width > $maxWidth) {
                $row = mb_substr($row, 0, $maxWidth - $width) . '…';
            }
            $row = str_replace(
                [$word->word, "\n", "\t"],
                [C::lred($word->word), C::cyan('\\n'), C::cyan('\\t')],
                $row
            );

            if ($word->block === true) {
                $foot .= ' ' . $word->rowNumber . ':' . C::gray(' unused ignore: ') . C::lred($word->word) . "\n";
            } else {
                $foot .= ' ' . $word->rowNumber . ':'
                    . ($word->context !== null ? C::gray(' (') . $word->context . C::gray(')') : '')
                    . C::gray(' "') . $row . C::gray('"') . "\n";
            }
        }
        $words = array_unique($words);
        sort($words);
        $words = ' ' . C::lred(implode(' ', $words));

        return $head . $words . "\n" . $foot;
    }

    public function formatErrorsShort(Result $result): string
    {
        $output = '';
        foreach ($result->getErrors() as $fileName => $errors) {
            $output .= $this->formatFileErrorsShort($fileName, $errors);
        }

        return $output;
    }

    /**
     * @param Word[] $errors
     * @return string
     */
    public function formatFileErrorsShort(string $fileName, array $errors): string
    {
        $output = '' . C::lcyan($this->stripBaseDir($fileName)) . C::gray(' (')
            . implode(',', $this->dictionaryResolver->getDictionariesForFileName($fileName)) . C::gray("):\n");
        $output .= implode(' ', array_unique(array_map(static function (Word $word) {
            return $word->word;
        }, $errors))) . "\n";

        return $output;
    }

    private function stripBaseDir(string $fileName): string
    {
        if ($this->baseDir === '') {
            return $fileName;
        }
        return substr($fileName, 0, strlen($this->baseDir)) === $this->baseDir
            ? substr($fileName, strlen($this->baseDir))
            : $fileName;
    }

}
