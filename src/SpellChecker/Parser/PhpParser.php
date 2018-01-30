<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use PhpParser\Parser\Tokens;

class PhpParser implements \SpellChecker\Parser\Parser
{

    public const CONTEXT_CODE = 'code';
    public const CONTEXT_STRING = 'string';
    public const CONTEXT_COMMENT = 'comment';
    public const CONTEXT_DOC = 'doc';
    public const CONTEXT_HTML = 'html';
    public const CONTEXT_DATA = 'data';

    /** @var \SpellChecker\Parser\PhpLexer */
    private $phpLexer;

    /** @var \SpellChecker\Parser\DefaultParser */
    private $defaultParser;

    public function __construct(DefaultParser $defaultParser)
    {
        $this->defaultParser = $defaultParser;
        $this->phpLexer = new PhpLexer([
            'usedAttributes' => ['startLine', 'startFilePos'],
        ]);
    }

    /**
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        $results = [];
        $lastHeredoc = null;
        $this->phpLexer->startLexing($string);
        while ($token = $this->phpLexer->getNextToken($value, $startAttributes, $endAttributes)) {
            if ($token < 256) {
                continue;
            }
            $rowNumber = $startAttributes['startLine'];
            $position = $startAttributes['startFilePos'];

            switch ($token) {
                case Tokens::T_COMMENT:
                    // // or #, and /* */
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_COMMENT);
                    break;
                case Tokens::T_DOC_COMMENT:
                    // /** */
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_DOC);
                    break;
                case Tokens::T_CONSTANT_ENCAPSED_STRING:
                    // "foo" or 'bar'
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_STRING);
                    break;
                case Tokens::T_START_HEREDOC:
                    // <<<FOO
                    $lastHeredoc = trim(substr($value, 3), "'\"\n");
                    $position += strlen(rtrim($value, "'\"\n")) - strlen($lastHeredoc);
                    $this->defaultParser->blocksToWords($lastHeredoc, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case Tokens::T_ENCAPSED_AND_WHITESPACE:
                    // parts of heredoc or string with variables
                    if ($lastHeredoc !== null && substr($value, -strlen($lastHeredoc) - 1) === $lastHeredoc . ';') {
                        $value = substr($value, 0, -strlen($lastHeredoc) - 1);
                        $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_STRING);
                        $offset = strlen($value);
                        $rows = strlen($value) - strlen(str_replace("\n", '', $value));
                        $this->defaultParser->blocksToWords($lastHeredoc, $position + $offset, $rowNumber + $rows, $results, self::CONTEXT_CODE);
                        $lastHeredoc = null;
                    } else {
                        $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_STRING);
                    }
                    break;
                case Tokens::T_STRING:
                    // identifiers, e.g. keywords like parent and self, function names, class names and more
                    $this->defaultParser->blocksToWords($value, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case Tokens::T_VARIABLE:
                    // $foo
                    $nameValue = ltrim($value, '$');
                    $position += strlen($value) - strlen($nameValue);
                    $this->defaultParser->blocksToWords($nameValue, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case Tokens::T_STRING_VARNAME:
                    $this->defaultParser->blocksToWords($value, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case Tokens::T_INLINE_HTML:
                    // any text outside <?php
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_HTML);
                    break;
                case Tokens::T_HALT_COMPILER:
                    // anything after __halt_compiler(); instructions
                    $after = $this->phpLexer->handleHaltCompiler();
                    $this->parseString($results, $after, $position + 18, $rowNumber, self::CONTEXT_DATA);
                    break;
            }
        }

        return $results;
    }

    private function parseString(array &$result, string $string, int $filePosition, int $rowNumber, string $context): void
    {
        $rowOffset = 0;
        foreach (explode("\n", $string) as $rowIndex => $row) {
            if (!preg_match_all(DefaultParser::WORD_BLOCK_REGEXP, $row, $blockMatches, PREG_OFFSET_CAPTURE)) {
                $rowOffset += strlen($row) + 1;
                continue;
            }
            foreach ($blockMatches[0] as [$block, $rowPosition]) {
                $this->defaultParser->blocksToWords(
                    $block,
                    $filePosition + $rowOffset + $rowPosition,
                    $rowNumber + $rowIndex,
                    $result,
                    $context
                );
            }
            $rowOffset += strlen($row) + 1;
        }
    }

}
