<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Console;

class ResultFormatter
{

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
        $output = '' . C::lcyan($fileName) . C::gray(":\n");
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
