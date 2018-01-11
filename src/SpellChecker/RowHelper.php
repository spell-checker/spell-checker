<?php declare(strict_types = 1);

namespace SpellChecker;

class RowHelper
{

    public static function getRowAtPosition(string $string, int $position): string
    {
        $before = substr($string, 0, $position);
        $rowStart = strrpos($before, "\n");
        if ($rowStart === false) {
            $rowStart = 0;
        }
        $rowEnd = strpos($string, "\n", $position);
        if ($rowEnd === false) {
            $rowEnd = strlen($string) - 1;
        }

        return substr($string, $rowStart + 1, $rowEnd - $rowStart - 1);
    }

}
