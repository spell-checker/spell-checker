<?php declare(strict_types = 1);

// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
// spell-check-ignore: aaa bbb ccc ddd eee fff ggg hhh iii jjj kkk lll EOT

namespace SpellChecker\Parser;

use SpellChecker\Word;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$defaultParser = new PlainTextParser();
$phpParser = new PhpParser($defaultParser);

// basics
$names = '<?php declare(aaa = 1)
namespace bbb
class ccc {
    const ddd = 0;
    private \$eee;
    function fff(ggg \$hhh): iii {
        jjj();
        \$kkk = \$\$lll;
    }
}';
$actual = $phpParser->parse($names);
$expected = [
    new Word('aaa', null,  14, 1, Context::CODE),
    new Word('bbb', null,  33, 2, Context::CODE),
    new Word('ccc', null,  43, 3, Context::CODE),
    new Word('ddd', null,  59, 4, Context::CODE),
    new Word('eee', null,  82, 5, Context::CODE),
    new Word('fff', null, 100, 6, Context::CODE),
    new Word('ggg', null, 104, 6, Context::CODE),
    new Word('hhh', null, 110, 6, Context::CODE),
    new Word('iii', null, 116, 6, Context::CODE),
    new Word('jjj', null, 130, 7, Context::CODE),
    new Word('kkk', null, 147, 8, Context::CODE),
    new Word('lll', null, 157, 8, Context::CODE),
];
Assert::equal($expected, $actual);


// quote strings
$string = '<?php
$aaa = "bbb
ccc";';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('bbb', null, 14, 2, Context::STRING),
    new Word('ccc', null, 18, 3, Context::STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);

// apostrophe strings
$string = '<?php
$aaa = \'bbb
ccc\'';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('bbb', null, 14, 2, Context::STRING),
    new Word('ccc', null, 18, 3, Context::STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);

// heredoc strings
$string = '<?php
$aaa = <<<EOT
bbb
ccc
EOT;';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('EOT', null, 16, 2, Context::CODE),
    new Word('bbb', null, 20, 3, Context::STRING),
    new Word('ccc', null, 24, 4, Context::STRING),
    new Word('EOT', null, 28, 5, Context::CODE),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);

// nowdoc strings
$string = '<?php
$aaa = <<<\'EOT\'
bbb
ccc
EOT;';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('EOT', null, 17, 2, Context::CODE),
    new Word('bbb', null, 22, 3, Context::STRING),
    new Word('ccc', null, 26, 4, Context::STRING),
    new Word('EOT', null, 30, 5, Context::CODE),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


// string interpolation
$string = '<?php
$aaa = "bbb $ccc ddd";';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('bbb', null, 14, 2, Context::STRING),
    new Word('ccc', null, 19, 2, Context::CODE),
    new Word('ddd', null, 23, 2, Context::STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);

$string = '<?php
$aaa = "bbb {$ccc} ddd";';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('bbb', null, 14, 2, Context::STRING),
    new Word('ccc', null, 20, 2, Context::CODE),
    new Word('ddd', null, 25, 2, Context::STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);

$string = '<?php
$aaa = "bbb {$ccc->ddd} eee";';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('bbb', null, 14, 2, Context::STRING),
    new Word('ccc', null, 20, 2, Context::CODE),
    new Word('ddd', null, 25, 2, Context::CODE),
    new Word('eee', null, 30, 2, Context::STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);

$string = '<?php
$aaa = "bbb {$ccc[\'ddd\']} eee"';
$expected = [
    new Word('aaa', null,  7, 2, Context::CODE),
    new Word('bbb', null, 14, 2, Context::STRING),
    new Word('ccc', null, 20, 2, Context::CODE),
    new Word('ddd', null, 25, 2, Context::STRING),
    new Word('eee', null, 32, 2, Context::STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


// html context
$html = '
aaa
bbb
<?php
$ccc = 1;
?>
ddd
eee';
$expected = [
    new Word('aaa', null,  1, 2, Context::HTML),
    new Word('bbb', null,  5, 3, Context::HTML),
    new Word('ccc', null, 16, 5, Context::CODE),
    new Word('ddd', null, 28, 7, Context::HTML),
    new Word('eee', null, 32, 8, Context::HTML),
];
$actual = $phpParser->parse($html);
Assert::equal($expected, $actual);


// data context
$halt = '<?php
__halt_compiler();
aaa
bbb';
$expected = [
    new Word('aaa', null, 25, 3, Context::DATA),
    new Word('bbb', null, 29, 4, Context::DATA),
];
$actual = $phpParser->parse($halt);
Assert::equal($expected, $actual);
