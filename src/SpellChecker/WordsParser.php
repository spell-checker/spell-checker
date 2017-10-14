<?php declare(strict_types = 1);

namespace SpellChecker;

class WordsParser
{

    /** @var bool[] */
    private static $exceptions = [
        'PHPUnit' => true,
    ];

    /**
     * Parse code with camelCase and under_scores
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        $result = [];

        if (!preg_match_all('/[\\p{L}0-9_]+/u', $string, $matches, PREG_OFFSET_CAPTURE)) {
            return $result;
        }
        foreach ($matches[0] as $match) {
            [$block, $position] = $match;
            $block = trim($block, '_');

            // skip numbers
            if (preg_match('/^[0-9]+$/', $block)) {
                continue;
            }

            if (strpos($block, '_') !== false) {
                // FOO_BAR or fooBar_barBaz
                $parts = explode('_', $block);
                $underscore = true;
            } else {
                $parts = [$block];
                $underscore = false;
            }

            $offset = 0;
            foreach ($parts as $part) {
                if (isset(self::$exceptions[$part])) {
                    // FOOBar
                    $result[] = new Word($part, $underscore ? $block : null, $position + $offset);
                } elseif (preg_match('/^[\\p{Lu}]+$/u', $part)) {
                    // FOO
                    $result[] = new Word($part, $underscore ? $block : null, $position + $offset);
                } else {
                    $words = array_values(array_filter(preg_split('/(?=[\\p{Lu}])/u', $part)));
                    if (count($words) === 1) {
                        // foo
                        $result[] = new Word($words[0], $underscore ? $block : null, $position + $offset);
                    } else {
                        // fooBar
                        $offset2 = 0;
                        foreach ($words as $word) {
                            if (preg_match('/^[0-9]+$/', $word)) {
                                continue;
                            }
                            $result[] = new Word($word, $block, $position + $offset + $offset2);
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
