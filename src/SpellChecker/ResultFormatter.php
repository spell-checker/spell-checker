<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Console;

class ResultFormatter
{

    /** @var \SpellChecker\DictionaryResolver */
    private $dictionaryResolver;

    public function __construct(DictionaryResolver $dictionaryResolver)
    {
        $this->dictionaryResolver = $dictionaryResolver;
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

    public function formatFilesList(array $files): string
    {
        $output = '';
        foreach ($files as $fileName) {
            $output .= C::lcyan($fileName) . "\n";
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
            $context = $this->dictionaryResolver->getContextForFileName($fileName);
            foreach ($fileErrors as $error) {
                $word = $error->word;
                if (isset($contexts[$context][$word])) {
                    $contexts[$context][$word]++;
                } else {
                    $contexts[$context][$word] = 1;
                }
            }
        }
        uasort($contexts, function (array $words1, array $words2) {
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

    public function formatTopBlocksByContext(Result $result): string
    {
        $contexts = [];
        foreach ($result->getErrors() as $fileName => $fileErrors) {
            $context = $this->dictionaryResolver->getContextForFileName($fileName);
            foreach ($fileErrors as $error) {
                $word = $error->block ?? $error->word;
                if (isset($contexts[$context][$word])) {
                    $contexts[$context][$word]++;
                } else {
                    $contexts[$context][$word] = 1;
                }
            }
        }
        uasort($contexts, function (array $words1, array $words2) {
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
                    //continue;
                }
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
     * @param string $fileName
     * @param \SpellChecker\Word[] $errors
     * @param int $maxWidth
     * @return string
     */
    public function formatFileErrors(string $fileName, array $errors, int $maxWidth): string
    {
        $output = '' . C::lcyan($fileName) . C::gray(' (')
            . $this->dictionaryResolver->getContextForFileName($fileName) .  C::gray("):\n");
        foreach ($errors as $word) {
            $row = $word->row;
            $width = mb_strlen($word->word . $row . $word->rowNumber) + 27;
            if ($width > $maxWidth) {
                $row = mb_substr($row, 0, $maxWidth - $width) . '…';
            }
            $row = str_replace(
                [$word->word, "\n", "\t"],
                [C::yellow($word->word), C::cyan('\\n'), C::cyan('\\t')],
                $row
            );

            $output .= C::gray(' - found "') . $word->word
                . C::gray('" in "') . $row
                . C::gray('" at row ') . $word->rowNumber . "\n";
        }

        return $output;
    }

}
