<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use PhpParser\Parser\Tokens;
use function ltrim;
use function rtrim;
use function str_replace;
use function strlen;
use function substr;
use function trim;

/**
 * Word parser for PHP language recognizing context "code", "string", "comment", "doc", "data" and "html"
 * Supports string variables interpolation. Variables inside strings are returned as "code".
 */
class PhpParser implements \SpellChecker\Parser\Parser
{

    /** @var \SpellChecker\Parser\PhpLexer */
    private $phpLexer;

    /** @var \SpellChecker\Parser\PlainTextParser */
    private $plainTextParser;

    public function __construct(PlainTextParser $plainTextParser)
    {
        $this->plainTextParser = $plainTextParser;
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
                    $this->plainTextParser->parseText($results, $value, $position, $rowNumber, Context::COMMENT);
                    break;
                case Tokens::T_DOC_COMMENT:
                    // /** */
                    $this->plainTextParser->parseText($results, $value, $position, $rowNumber, Context::DOC);
                    break;
                case Tokens::T_CONSTANT_ENCAPSED_STRING:
                    // "foo" or 'bar'
                    $this->plainTextParser->parseText($results, $value, $position, $rowNumber, Context::STRING);
                    break;
                case Tokens::T_START_HEREDOC:
                    // <<<FOO
                    $lastHeredoc = trim(substr($value, 3), "'\"\n");
                    $position += strlen(rtrim($value, "'\"\n")) - strlen($lastHeredoc);
                    $this->plainTextParser->blocksToWords($lastHeredoc, $position, $rowNumber, $results, Context::CODE);
                    break;
                case Tokens::T_ENCAPSED_AND_WHITESPACE:
                    // parts of heredoc or string with variables
                    if ($lastHeredoc !== null && substr($value, -strlen($lastHeredoc) - 1) === $lastHeredoc . ';') {
                        $value = substr($value, 0, -strlen($lastHeredoc) - 1);
                        $this->plainTextParser->parseText($results, $value, $position, $rowNumber, Context::STRING);
                        $offset = strlen($value);
                        $rows = strlen($value) - strlen(str_replace("\n", '', $value));
                        $this->plainTextParser->blocksToWords($lastHeredoc, $position + $offset, $rowNumber + $rows, $results, Context::CODE);
                        $lastHeredoc = null;
                    } else {
                        $this->plainTextParser->parseText($results, $value, $position, $rowNumber, Context::STRING);
                    }
                    break;
                case Tokens::T_STRING:
                    // identifiers, e.g. keywords like parent and self, function names, class names and more
                    $this->plainTextParser->blocksToWords($value, $position, $rowNumber, $results, Context::CODE);
                    break;
                case Tokens::T_VARIABLE:
                    // $foo
                    $nameValue = ltrim($value, '$');
                    $position += strlen($value) - strlen($nameValue);
                    $this->plainTextParser->blocksToWords($nameValue, $position, $rowNumber, $results, Context::CODE);
                    break;
                case Tokens::T_STRING_VARNAME:
                    $this->plainTextParser->blocksToWords($value, $position, $rowNumber, $results, Context::CODE);
                    break;
                case Tokens::T_INLINE_HTML:
                    // any text outside <?php
                    $this->plainTextParser->parseText($results, $value, $position, $rowNumber, Context::HTML);
                    break;
                case Tokens::T_HALT_COMPILER:
                    // anything after __halt_compiler(); instructions
                    $after = $this->phpLexer->handleHaltCompiler();
                    $this->plainTextParser->parseText($results, $after, $position + 18, $rowNumber, Context::DATA);
                    break;
            }
        }

        return $results;
    }

}
