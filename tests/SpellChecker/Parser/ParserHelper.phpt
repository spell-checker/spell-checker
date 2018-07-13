<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use Tester\Assert;

require '../../bootstrap.php';

$string = 'foo
bar
foo
bar';

$result = ParserHelper::getRowStarts($string);

Assert::same($result, [0, 3, 7, 11]);
