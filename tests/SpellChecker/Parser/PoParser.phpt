<?php declare(strict_types = 1);

// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
// spell-check-ignore: více akcí viac akcií akce akcia akcie

namespace SpellChecker\Parser;

use SpellChecker\Word;
use Tester\Assert;
use function file_get_contents;

require __DIR__ . '/../../bootstrap.php';

$defaultParser = new DefaultParser();
$poParser = new PoParser($defaultParser);

$testFile = file_get_contents(__DIR__ . '/PoParserTestFile.po');
$result = $poParser->parse($testFile);

Assert::equal([
    new Word('d',     null, 763, 24, 'msgid'),
    new Word('více',  null, 765, 24, 'msgid'),
    new Word('d',     null, 781, 25, 'msgstr'),
    new Word('viac',  null, 783, 25, 'msgstr'),
    new Word('d',     null, 812, 28, 'msgid'),
    new Word('akce',  null, 814, 28, 'msgid'),
    new Word('d',     null, 835, 29, 'msgid'),
    new Word('akcí',  null, 837, 29, 'msgid'),
    new Word('d',     null, 856, 30, 'msgstr'),
    new Word('akcia', null, 858, 30, 'msgstr'),
    new Word('d',     null, 877, 31, 'msgstr'),
    new Word('akcie', null, 879, 31, 'msgstr'),
    new Word('d',     null, 898, 32, 'msgstr'),
    new Word('akcií', null, 900, 32, 'msgstr'),
], $result);
