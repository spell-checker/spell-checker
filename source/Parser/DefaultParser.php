<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use SpellChecker\Word;
use function array_filter;
use function array_map;
use function array_unshift;
use function array_values;
use function count;
use function in_array;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function strlen;
use function strpos;
use function trim;
use const PREG_OFFSET_CAPTURE;

class DefaultParser implements Parser
{

    public const string WORD_BLOCK_REGEXP = '/[\\p{L}0-9_-]+/u';

    /** @var string[] */
    private array $exceptions;

    /**
     * @param string[] $exceptions
     */
    public function __construct(array $exceptions = [])
    {
        $this->exceptions = $exceptions;
    }

    /**
     * Parse code with camelCase and under_scores
     * @return Word[]
     */
    public function parse(string $string): array
    {
        $result = [];

        if (!preg_match_all(self::WORD_BLOCK_REGEXP, $string, $blockMatches, PREG_OFFSET_CAPTURE)) {
            return $result;
        }

        preg_match_all("/\n/", $string, $rowMatches, PREG_OFFSET_CAPTURE);
        /** @var int[] $rowStarts ($start => $row) */
        $rowStarts = array_map(static function (array $rowMatch): int {
            return $rowMatch[1];
        }, $rowMatches[0]);
        array_unshift($rowStarts, 0);
        $rowStarts[] = strlen($string);

        $rowNumber = 1;
        foreach ($blockMatches[0] as [$block, $position]) {
            while ($position >= $rowStarts[$rowNumber]) {
                $rowNumber++;
            }
            $this->blocksToWords($block, $position, $rowNumber, $result);
        }

        return $result;
    }

    /**
     * @param Word[] $result
     */
    public function blocksToWords(string $block, int $position, int $rowNumber, array &$result, ?string $context = null): void
    {
        $prefixLength = strlen($block) - strlen(ltrim($block, '_-'));
        $position += $prefixLength;
        $block = trim($block, '_-');

        // skip numbers
        if (preg_match('/^[0-9_-]+$/', $block)) {
            return;
        }

        if (strpos($block, '_') !== false || strpos($block, '-') !== false) {
            // FOO_BAR or fooBar_barBaz or e-mail
            $parts = preg_split('/[_-]/', $block);
            $split = true;
        } else {
            $parts = [$block];
            $split = false;
        }

        $offset = 0;
        /** @var string|null $prefixNext */
        $prefixNext = null;
        foreach ($parts as $i => $part) {
            // fucking e-mail exception
            if (($part === 'e' || $part === 'E')
                && isset($parts[$i + 1])
                && strpos($block, '-') === 1
            ) {
                $prefixNext = $part . '-';
                continue;
            }
            if ($prefixNext !== null) {
                $part = $prefixNext . $part;
                if ($part === $block) {
                    $split = false;
                }
                $prefixNext = null;
            }

            if (in_array($part, $this->exceptions, true)) {
                // FOOBar
                $result[] = new Word($part, $split ? $block : null, $position + $offset, $rowNumber, $context);
            } elseif (preg_match('/^[\\p{Lu}]+$/u', $part)) {
                // FOO
                $result[] = new Word($part, $split ? $block : null, $position + $offset, $rowNumber, $context);
            } else {
                $words = array_values(array_filter(preg_split('/(?=[\\p{Lu}])/u', $part)));
                if (count($words) === 1) {
                    // foo
                    $result[] = new Word($words[0], $split ? $block : null, $position + $offset, $rowNumber, $context);
                } else {
                    // fooBar
                    $offset2 = 0;
                    foreach ($words as $word) {
                        if (preg_match('/^[0-9]+$/', $word)) {
                            continue;
                        }
                        $result[] = new Word($word, $block, $position + $offset + $offset2, $rowNumber, $context);
                        $offset2 += strlen($word);
                    }
                }
            }
            $offset += strlen($part) + 1;
        }
    }

    /**
     * Parse native language
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
