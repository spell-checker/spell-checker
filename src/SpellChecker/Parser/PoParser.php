<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use const PREG_OFFSET_CAPTURE;
use function explode;
use function preg_match;
use function preg_match_all;
use function strlen;

/**
 * Parser for .po translation files recognizing contexts "string" for original messages and "trans" for translated messages.
 */
class PoParser implements \SpellChecker\Parser\Parser
{

    /** @var \SpellChecker\Parser\PlainTextParser */
    private $plainTextParser;

    public function __construct(PlainTextParser $plainTextParser)
    {
        $this->plainTextParser = $plainTextParser;
    }

    /**
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        $result = [];

        $rows = explode("\n", $string);
        $rowEnd = 0;
        foreach ($rows as $rowIndex => $row) {
            $rowStart = $rowEnd;
            $rowEnd = $rowStart + strlen($row) + 1;
            if (!preg_match('/^(msgid|msgid_plural|msgstr)(\\[\\d+\\])? "(.*)"$/', $row, $match)) {
                continue;
            }
            $context = $match[1] === 'msgstr' ? Context::TRANSLATION : Context::STRING;
            $rowOffset = strlen($match[1]) + strlen($match[2]) + 2;
            if (!preg_match_all(PlainTextParser::WORD_BLOCK_REGEXP, $match[3], $blockMatches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($blockMatches[0] as [$block, $blockPosition]) {
                if ($block === 'msgstr') {
                    continue;
                }
                $position = $rowStart + $rowOffset + $blockPosition;
                $this->plainTextParser->blocksToWords($block, $position, $rowIndex + 1, $result, $context);
            }
        }

        return $result;
    }

}
