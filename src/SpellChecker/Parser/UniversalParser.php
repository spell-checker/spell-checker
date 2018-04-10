<?php declare(strict_types = 1);

namespace SpellChecker\Parser\UniversalParser;

use SpellChecker\Parser\Context;
use SpellChecker\Parser\DefaultParser;

class UniversalParser
{

    private const NUMBERS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    /** @var int[] */
    private static $numbersKey;

    /** @var int[] */
    private static $nameCharsKey;

    /** @var \SpellChecker\Parser\DefaultParser */
    private $defaultParser;

    /** @var \SpellChecker\Parser\UniversalParser\UniversalParserSettings */
    private $settings;

    /** @var string[] */
    private $keywordsKey;

    /**
     * @param \SpellChecker\Parser\DefaultParser $defaultParser
     * @param \SpellChecker\Parser\UniversalParser\UniversalParserSettings $settings
     * @param string[] $keywords
     */
    public function __construct(DefaultParser $defaultParser, UniversalParserSettings $settings, array $keywords = [])
    {
        $this->defaultParser = $defaultParser;
        $this->settings = $settings;
        $this->keywordsKey = array_flip($keywords);

        if (self::$numbersKey === null) {
            self::$numbersKey = array_flip(self::NUMBERS);
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['_', '-']));
        }
    }

    /**
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        $results = [];

        $position = 0;
        $rowNumber = 1;

        while (($char = $string[$position]) !== false) {
            $position++;

            switch ($char) {
                case "\x00":
                case "\x01":
                case "\x02":
                case "\x03":
                case "\x04":
                case "\x05":
                case "\x06":
                case "\x07":
                case "\x08":
                case "\t":
                    break;
                case "\n":
                    $rowNumber++;
                    break;
                case "\x0B":
                case "\f":
                case "\r":
                case "\x0E":
                case "\x0F":
                case "\x10":
                case "\x11":
                case "\x12":
                case "\x13":
                case "\x14":
                case "\x15":
                case "\x16":
                case "\x17":
                case "\x18":
                case "\x19":
                case "\x1A":
                case "\e":
                case "\x1C":
                case "\x1D":
                case "\x1E":
                case "\x1F":
                case "\x7F":
                    break;
                case '\'':
                    if ($this->settings->tripleApostropheStrings !== null && $string[$position] === "'" && $string[$position + 1] === "'") {
                        // ''' foo '''
                        $this->parseUntil($results, $string, $position, $rowNumber, "'''", $this->settings->tripleApostropheStrings);
                    } elseif ($this->settings->apostropheComments !== null) {
                        // ' foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->apostropheComments);
                    } elseif ($this->settings->apostropheStrings !== null) {
                        // 'foo'
                        $this->parseString($results, $string, $position, $rowNumber, "'", $this->settings->apostropheStrings);
                    }
                    break;
                case '*':
                    if ($this->settings->asteriskComments !== null) {
                        // * foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->asteriskComments);
                    } elseif ($this->settings->asteriskArrowComments !== null) {
                        // *> foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->asteriskArrowComments);
                    } elseif ($this->settings->sasBlockComments !== null) {
                        // * foo ;
                        $this->parseUntil($results, $string, $position, $rowNumber, ';', $this->settings->sasBlockComments);
                    }
                    break;
                case '\\':
                    if ($this->settings->backslashComments !== null) {
                        // \ foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->backslashComments);
                    }
                    break;
                case '`':
                    if ($this->settings->backtickComments !== null) {
                        // ` foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->backtickComments);
                    } elseif ($this->settings->backtickStrings !== null) {
                        // `foo`
                        $this->parseString($results, $string, $position, $rowNumber, "`", $this->settings->backtickStrings);
                    }
                    break;
                case ':':
                    if ($this->settings->doubleColonComments !== null && $string[$position] === ':') {
                        // :: foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->doubleColonComments);
                    }
                    break;
                case '!':
                    if ($this->settings->exclamationComments !== null) {
                        // ! foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->exclamationComments);
                    }
                    break;
                case '#':
                    if ($this->settings->tripleHashBlockComments !== null && $string[$position] === '#' && $string[$position + 1] === '#') {
                        // ### foo ###
                        $this->parseUntil($results, $string, $position, $rowNumber, '###', $this->settings->tripleHashBlockComments);
                    } elseif ($this->settings->lispBlockComments !== null && $string[$position] === '|') {
                        // #| foo |#
                        $this->parseUntil($results, $string, $position, $rowNumber, '|#', $this->settings->lispBlockComments);
                    } elseif ($this->settings->hashComments !== null) {
                        // # foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->hashComments);
                    }
                    break;
                case '-';
                    if ($this->settings->luaBlockComments !== null && $string[$position] === '-' && $string[$position + 1] === '[' && $string[$position + 2] === '[') {
                        // --[[ foo ]]
                        $this->parseUntil($results, $string, $position, $rowNumber, ']]', $this->settings->luaBlockComments);
                    } elseif ($this->settings->doubleHyphenComments !== null && $string[$position] === '-') {
                        // -- foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->doubleHyphenComments);
                    }
                    break;
                case '%':
                    if ($this->settings->percentComments !== null) {
                        // % foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->percentComments);
                    }
                    break;
                case '"':
                    if ($this->settings->tripleQuoteStrings !== null && $string[$position] === '"' && $string[$position + 1] === '"') {
                        // """foo"""
                        $this->parseString($results, $string, $position, $rowNumber, '"""', $this->settings->tripleQuoteStrings);
                    } elseif ($this->settings->quoteStrings !== null) {
                        // "foo"
                        $this->parseString($results, $string, $position, $rowNumber, '"', $this->settings->quoteStrings);
                    } elseif ($this->settings->quoteSlashComments !== null) {
                        // "/ foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->quoteSlashComments);
                    } elseif ($this->settings->quoteComments !== null) {
                        // " foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->quoteComments);
                    }
                    break;
                case ';':
                    if ($this->settings->semicolonComments !== null) {
                        // ; foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->semicolonComments);
                    }
                    break;
                case '/':
                    if ($this->settings->tripleSlashComments !== null && $string[$position] === '/' && $string[$position + 1] === '/') {
                        // /// foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->tripleSlashComments);
                    } elseif ($this->settings->slashSlashBangComments !== null && $string[$position] === '/' && $string[$position + 1] === '!') {
                        // //! foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->slashSlashBangComments);
                    } elseif ($this->settings->doubleSlashComments !== null && $string[$position] === '/') {
                        // // foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->doubleSlashComments);
                    } elseif ($this->settings->docBlockComments !== null && $string[$position] === '*' && $string[$position + 1] === '*') {
                        // /** foo */
                        $this->parseUntil($results, $string, $position, $rowNumber, '*/', $this->settings->docBlockComments);
                    } elseif ($this->settings->cBlockComments !== null && $string[$position] === '*') {
                        // /* foo */
                        $this->parseUntil($results, $string, $position, $rowNumber, '*/', $this->settings->cBlockComments);
                    } elseif ($this->settings->dBlockComments !== null &&  $string[$position] === '+') {
                        // /+ foo +/
                        $this->parseUntil($results, $string, $position, $rowNumber, '+/', $this->settings->dBlockComments);
                    } elseif ($this->settings->slashComments !== null) {
                        // / foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->slashComments);
                    }
                    break;
                case '(':
                    if ($this->settings->pascalBlockComments !== null && $string[$position] === '*') {
                        // (* foo *)
                        $this->parseUntil($results, $string, $position, $rowNumber, '*)', $this->settings->pascalBlockComments);
                    }
                    break;
                case '{':
                    if ($this->settings->latteBlockComments !== null && $string[$position] === '*') {
                        // {* foo *}
                        $this->parseUntil($results, $string, $position, $rowNumber, '*}', $this->settings->latteBlockComments);
                    } elseif ($this->settings->haskelBlockComments !== null && $string[$position] === '-') {
                        // {- foo -}
                        $this->parseUntil($results, $string, $position, $rowNumber, '-}', $this->settings->haskelBlockComments);
                    } elseif ($this->settings->twigBlockComments !== null && $string[$position] === '#') {
                        // {# foo #}
                        $this->parseUntil($results, $string, $position, $rowNumber, '#}', $this->settings->twigBlockComments);
                    } elseif ($this->settings->curlyBracketBlockComments !== null) {
                        // { foo }
                        $this->parseUntil($results, $string, $position, $rowNumber, '}', $this->settings->curlyBracketBlockComments);
                    }
                    break;
                case '<':
                    if ($this->settings->powerShellBlockComments !== null && $string[$position] === '#') {
                        // <# foo #>
                        $this->parseUntil($results, $string, $position, $rowNumber, '#>', $this->settings->powerShellBlockComments);
                    } elseif ($this->settings->xmlBlockComments !== null && $string[$position] === '!' && $string[$position + 1] === '-' && $string[$position + 2] === '-') {
                        // <!-- foo -->
                        $this->parseUntil($results, $string, $position, $rowNumber, '-->', $this->settings->xmlBlockComments);
                    } elseif ($this->settings->xmlTags !== null) {
                        ///
                    }
                    break;
                case '=':
                    if ($this->settings->rubyBlockComments !== null && substr($string, $position, 5) === 'begin') {
                        // =begin foo =cut
                        $this->parseUntil($results, $string, $position, $rowNumber, '=end', $this->settings->rubyBlockComments);
                    } elseif ($this->settings->perlBlockComments !== null && $string[$position - 2] === "\n") {
                        // =pod foo =cut
                        $this->parseUntil($results, $string, $position, $rowNumber, '=cut', $this->settings->perlBlockComments);
                    }
                    break;
                case '$':
                case '&':
                case '+':
                case '^':
                case '|':
                case '~':
                case '.':
                case ',':
                case '?':
                case '@':
                case ')':
                case ']':
                case '}':
                case '>':
                case ' ';
                    // ignore
                    break;
                default:
                    // letters, numbers, "_", and codes over 127
                    $value = $char;
                    $startPosition = $position - 1;
                    while (($next = substr($string, $position, 1)) !== false) {
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    if (isset($keywordsKey[$value])) {
                        break;
                    }
                    $this->defaultParser->blocksToWords($value, $startPosition, $rowNumber, $results, Context::CODE);
                    break;
            }
        }

        return $results;
    }

    private function parseUntil(array &$results, string &$string, int &$position, int &$rowNumber, string $end, string $context): void
    {
    	$content = '';
    	$position++;
    	$char = $string[$position];
    	while ($char !== '') {


			$position++;
			$char = $string[$position];
		}

		if ($content !== '') {
    		$results[] =
		}
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

    /**
     * @param \SpellChecker\Word[] $result
     * @param string $string
     * @param int $position
     * @param int $rowNumber
     * @param string $quote
     */
    private function parseStringx(array &$result, string &$string, int &$position, int &$rowNumber, string $quote, string $context): void
    {
        $backslashes = !$this->settings->getMode()->contains(Mode::NO_BACKSLASH_ESCAPES);

        $orig[] = $quote;
        $escaped = false;
        while ($next = $string[$position]) {
            if ($next === false) {
                throw new \SpellChecker\Parser\UniversalParser\EndOfStringNotFoundException();
            } elseif ($next === $quote) {
                $orig[] = $next;
                $position++;
                if ($escaped) {
                    $escaped = false;
                } elseif ($string[$position] === $quote) {
                    $escaped = true;
                } else {
                    break;
                }
            } elseif ($next === "\n") {
                $orig[] = $next;
                $position++;
                $rowNumber++;
            } elseif ($backslashes && $next === '\\') {
                $escaped = !$escaped;
                $orig[] = $next;
                $position++;
            } else {
                $orig[] = $next;
                $position++;
            }
        }

        $orig = implode('', $orig);
        $value = $this->unescapeString($orig, $quote);

        return [$value, $orig];
    }

    /**
     * NO_BACKSLASH_ESCAPES mode:
     * Disable the use of the backslash character (\) as an escape character within strings.
     * With this mode enabled, backslash becomes an ordinary character like any other.
     *
     * \0	An ASCII NUL (X'00') character
     * \'	A single quote (') character
     * \"	A double quote (") character
     * \b	A backspace character
     * \n	A newline (linefeed) character
     * \r	A carriage return character
     * \t	A tab character
     * \Z	ASCII 26 (Control+Z)
     * \\	A backslash (\) character
     *
     * (do not unescape. keep original for LIKE)
     * \%	A % character
     * \_	A _ character
     *
     * A ' inside a string quoted with ' may be written as ''.
     * A " inside a string quoted with " may be written as "".
     */
    private function unescapeString(string $string, string $quote): string
    {
        $translations = [
            '\\0' => "\x00",
            '\\\'' => '\'',
            '\\""' => '""',
            '\\b' => "\x08",
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\Z' => "\x1A",
            '\\\\' => '\\',
        ];

        $string = substr($string, 1, -1);

        $string = str_replace($quote . $quote, $quote, $string);
        if (!$this->settings->getMode()->contains(Mode::NO_BACKSLASH_ESCAPES)) {
            $string = str_replace(array_keys($translations), array_values($translations), $string);

            ///
        }

        return $string;
    }

}
