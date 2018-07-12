<?php declare(strict_types = 1);

// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
// spell-check-ignore: Nepodarilo sa prihlásiť používateľa zlavomat e-mailů

namespace SpellChecker\Parser;

use SpellChecker\Word;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = new DefaultParser(['PHPUnit']);

Assert::equal([], $parser->parse(''));

Assert::equal([
    new Word('e-mailů', null, 0, 1),
], $parser->parse('e-mailů'));

Assert::equal([
    new Word('foo', null, 0, 1),
], $parser->parse('foo'));

Assert::equal([
    new Word('foo', null, 0, 1),
    new Word('bar', null, 4, 1),
], $parser->parse('foo bar'));

Assert::equal([
    new Word('foo', 'fooBar', 0, 1),
    new Word('Bar', 'fooBar', 3, 1),
], $parser->parse('fooBar'));

Assert::equal([
    new Word('FOO', 'FOO_BAR', 0, 1),
    new Word('BAR', 'FOO_BAR', 4, 1),
], $parser->parse('FOO_BAR'));

Assert::equal([
    new Word('foo123bar', null, 0, 1),
], $parser->parse('foo123bar'));

Assert::equal([
    new Word('PHPUnit',   'PHPUnit_Framework_MockObject', 0,  1),
    new Word('Framework', 'PHPUnit_Framework_MockObject', 8,  1),
    new Word('Mock',      'PHPUnit_Framework_MockObject', 18, 1),
    new Word('Object',    'PHPUnit_Framework_MockObject', 22, 1),
], $parser->parse('PHPUnit_Framework_MockObject'));

Assert::equal(
    ['result', 'Error', 'Text', 'Nepodarilo', 'sa', 'prihlásiť', 'používateľa'],
    $parser->parseSimple("|| \$result->ErrorText === 'Nepodarilo sa prihlásiť používateľa.")
);

Assert::equal([
    new Word('result',       null, 4,  1),
    new Word('Error', 'ErrorText', 12, 1),
    new Word('Text',  'ErrorText', 17, 1),
    new Word('Nepodarilo',   null, 27, 1),
    new Word('sa',           null, 38, 1),
    new Word('prihlásiť',    null, 41, 1),
    new Word('používateľa',  null, 53, 1),
], $parser->parse("|| \$result->ErrorText === 'Nepodarilo sa prihlásiť používateľa."));
