<?php declare(strict_types = 1);

namespace SpellChecker;

class WordsParser
{

    /** @var string[] */
    private $exceptions;

    /**
     * @param string[] $exceptions
     */
    public function __construct(array $exceptions = [])
    {
        $this->exceptions = $exceptions;
    }

    /**
     * Parse code with camelCase and under_scores
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        $result = [];

        if (!preg_match_all('/[\\p{L}0-9_-]+/u', $string, $blockMatches, PREG_OFFSET_CAPTURE)) {
            return $result;
        }

        preg_match_all("/\n/", $string, $rowMatches, PREG_OFFSET_CAPTURE);
        /** @var int[] $rowStarts ($start => $row) */
        $rowStarts = array_map(function (array $rowMatch): int {
            return $rowMatch[1];
        }, $rowMatches[0]);
        array_unshift($rowStarts, 0);
        array_push($rowStarts, strlen($string));

        $rowNumber = 1;
        $rowStart = 0;
        $rowEnd = $rowStarts[$rowNumber];

        foreach ($blockMatches[0] as [$block, $position]) {
            while ($position >= $rowEnd) {
                $rowNumber++;
                $rowStart = $rowEnd + 1;
                $rowEnd = $rowStarts[$rowNumber];
            }
            $block = trim($block, '_-');

            // skip numbers
            if (preg_match('/^[0-9_-]+$/', $block)) {
                continue;
            }

            if (strpos($block, '_') !== false || strpos($block, '-') !== false) {
                // FOO_BAR or fooBar_barBaz or e-mail
                $parts = preg_split('/[_-]/', $block);
                $underscore = true;
            } else {
                $parts = [$block];
                $underscore = false;
            }

            $offset = 0;
            foreach ($parts as $part) {
                if (in_array($part, $this->exceptions)) {
                    // FOOBar
                    $result[] = new Word($part, $underscore ? $block : null, $position + $offset, $rowNumber, $rowStart, $rowEnd);
                } elseif (preg_match('/^[\\p{Lu}]+$/u', $part)) {
                    // FOO
                    $result[] = new Word($part, $underscore ? $block : null, $position + $offset, $rowNumber, $rowStart, $rowEnd);
                } else {
                    $words = array_values(array_filter(preg_split('/(?=[\\p{Lu}])/u', $part)));
                    if (count($words) === 1) {
                        // foo
                        $result[] = new Word($words[0], $underscore ? $block : null, $position + $offset, $rowNumber, $rowStart, $rowEnd);
                    } else {
                        // fooBar
                        $offset2 = 0;
                        foreach ($words as $word) {
                            if (preg_match('/^[0-9]+$/', $word)) {
                                continue;
                            }
                            $result[] = new Word($word, $block, $position + $offset + $offset2, $rowNumber, $rowStart, $rowEnd);
                            $offset2 += strlen($word);
                        }
                    }
                }
                $offset += strlen($part) + 1;
            }
        }

        return $result;
    }

    /**
     * Parse native language
     * @param string $string
     * @return string[]
     */
    public function parseSimple(string $string): array
    {
        $words = $this->parse($string);
        $simple = [];
        foreach ($words as $word) {
            $simple[] = $word->word;
        }

        return $simple;
    }

}
