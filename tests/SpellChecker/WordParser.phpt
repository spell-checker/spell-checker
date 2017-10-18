<?php declare(strict_types = 1);

namespace SpellChecker;

use Tester\Assert;

require '../bootstrap.php';

$parser = new WordsParser(['PHPUnit']);

Assert::equal([], $parser->parse(''));

Assert::equal([
    new Word('foo', null, 0)
], $parser->parse('foo'));

Assert::equal([
    new Word('foo', null, 0),
    new Word('bar', null, 4),
], $parser->parse('foo bar'));

Assert::equal([
    new Word('foo', 'fooBar', 0),
    new Word('Bar', 'fooBar', 3),
], $parser->parse('fooBar'));

Assert::equal([
    new Word('FOO', 'FOO_BAR', 0),
    new Word('BAR', 'FOO_BAR', 4),
], $parser->parse('FOO_BAR'));

Assert::equal([
    new Word('foo123bar', null, 0),
], $parser->parse('foo123bar'));

Assert::equal([
    new Word('PHPUnit', 'PHPUnit_Framework_MockObject', 0),
    new Word('Framework', 'PHPUnit_Framework_MockObject', 8),
    new Word('Mock', 'PHPUnit_Framework_MockObject', 18),
    new Word('Object', 'PHPUnit_Framework_MockObject', 22),
], $parser->parse('PHPUnit_Framework_MockObject'));

Assert::equal(
    ['result', 'Error', 'Text', 'Nepodarilo', 'sa', 'prihlásiť', 'používateľa'],
    $parser->parseSimple("|| \$result->ErrorText === 'Nepodarilo sa prihlásiť používateľa.")
);

Assert::equal([
    new Word('result', null, 4),
    new Word('Error', 'ErrorText', 12),
    new Word('Text', 'ErrorText', 17),
    new Word('Nepodarilo', null, 27),
    new Word('sa', null, 38),
    new Word('prihlásiť', null, 41),
    new Word('používateľa', null, 53),
], $parser->parse("|| \$result->ErrorText === 'Nepodarilo sa prihlásiť používateľa."));