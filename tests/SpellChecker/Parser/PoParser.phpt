<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use SpellChecker\Word;
use Tester\Assert;

require '../../bootstrap.php';

$defaultParser = new DefaultParser();
$poParser = new PoParser($defaultParser);

$testFile = file_get_contents(__DIR__ . '/PoParserTestFile.po');
$result = $poParser->parse($testFile);

Assert::equal([
    new Word('d', null, 763, 24, 755, 772, 'msgid'),
    new Word('více', null, 765, 24, 755, 772, 'msgid'),
    new Word('d', null, 781, 25, 772, 789, 'msgstr'),
    new Word('viac', null, 783, 25, 772, 789, 'msgstr'),
    new Word('d', null, 812, 28, 804, 820, 'msgid'),
    new Word('akce', null, 814, 28, 804, 820, 'msgid'),
    new Word('d', null, 835, 29, 820, 844, 'msgid'),
    new Word('akcí', null, 837, 29, 820, 844, 'msgid'),
    new Word('d', null, 856, 30, 844, 865, 'msgstr'),
    new Word('akcia', null, 858, 30, 844, 865, 'msgstr'),
    new Word('d', null, 877, 31, 865, 886, 'msgstr'),
    new Word('akcie', null, 879, 31, 865, 886, 'msgstr'),
    new Word('d', null, 898, 32, 886, 908, 'msgstr'),
    new Word('akcií', null, 900, 32, 886, 908, 'msgstr'),
], $result);
