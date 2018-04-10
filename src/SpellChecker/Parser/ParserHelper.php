<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class ParserHelper
{

    /**
     * @param string $string
     * @return int[]
     */
    public static function getRowStarts(string $string): array
    {
        if (!preg_match_all('/\\n/', $string, $matches, PREG_OFFSET_CAPTURE)) {
            return [0];
        }

        $rowStarts = array_map(function (array $match): int {
            return $match[1];
        }, $matches[0]);

        array_unshift($rowStarts, 0);

        return $rowStarts;
    }

}
