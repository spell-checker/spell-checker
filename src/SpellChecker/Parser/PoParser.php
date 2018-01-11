<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class PoParser implements \SpellChecker\Parser\Parser
{

    public const CONTEXT_MESSAGE = 'msgid';
    public const CONTEXT_TRANSLATION = 'msgstr';

    /** @var \SpellChecker\Parser\DefaultParser */
    private $defaultParser;

    public function __construct(DefaultParser $defaultParser)
    {
        $this->defaultParser = $defaultParser;
    }

    /**
     * Parses only translations, not message ids
     * @param string $string
     * @return string[]
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
            $context = $match[1] === 'msgstr' ? self::CONTEXT_TRANSLATION : self::CONTEXT_MESSAGE;
            $rowOffset = strlen($match[1]) + strlen($match[2]) + 2;
            if (!preg_match_all(DefaultParser::WORD_BLOCK_REGEXP, $match[3], $blockMatches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($blockMatches[0] as [$block, $blockPosition]) {
                if ($block === 'msgstr') {
                    continue;
                }
                $position = $rowStart + $rowOffset + $blockPosition;
                $this->defaultParser->blocksToWords($block, $position, $rowIndex + 1, $result, $context);
            }
        }

        return $result;
    }

}
