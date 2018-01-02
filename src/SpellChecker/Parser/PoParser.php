<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class PoParser implements \SpellChecker\Parser\Parser
{

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
            if (substr($row, 0, 8) !== 'msgstr "') {
                continue;
            }
            if (!preg_match_all('/[\\p{L}0-9_-]+/u', $row, $blockMatches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($blockMatches[0] as [$block, $position]) {
                if ($block === 'msgstr') {
                    continue;
                }
                $this->defaultParser->blocksToWords($block, $rowStart + $position, $rowIndex + 1, $rowStart, $rowEnd, $result);
            }
        }

        return $result;
    }

}
