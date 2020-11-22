<?php declare(strict_types = 1);

// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
// spell-check-ignore: aaa bbb ccc ddd eee fff ggg hhh iii jjj kkk lll EOT

namespace SpellChecker\Parser;

use SpellChecker\Word;
use Tester\Assert;
use const PHP_VERSION_ID;

require __DIR__ . '/../../bootstrap.php';

$defaultParser = new DefaultParser();
$phpParser = new PhpParser($defaultParser);


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
    new Word('aaa', null,  14, 1, PhpParser::CONTEXT_CODE),
    new Word('bbb', null,  33, 2, PhpParser::CONTEXT_CODE),
    new Word('ccc', null,  43, 3, PhpParser::CONTEXT_CODE),
    new Word('ddd', null,  59, 4, PhpParser::CONTEXT_CODE),
    new Word('eee', null,  82, 5, PhpParser::CONTEXT_CODE),
    new Word('fff', null, 100, 6, PhpParser::CONTEXT_CODE),
    new Word('ggg', null, 104, 6, PhpParser::CONTEXT_CODE),
    new Word('hhh', null, 110, 6, PhpParser::CONTEXT_CODE),
    new Word('iii', null, 116, 6, PhpParser::CONTEXT_CODE),
    new Word('jjj', null, 130, 7, PhpParser::CONTEXT_CODE),
    new Word('kkk', null, 147, 8, PhpParser::CONTEXT_CODE),
    new Word('lll', null, 157, 8, PhpParser::CONTEXT_CODE),
];
Assert::equal($expected, $actual);


$string = '<?php
$aaa = "bbb
ccc";';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 14, 2, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 18, 3, PhpParser::CONTEXT_STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = \'bbb
ccc\'';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 14, 2, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 18, 3, PhpParser::CONTEXT_STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = <<<EOT
bbb
ccc
EOT;';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('EOT', null, 16, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 20, 3, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 24, 4, PhpParser::CONTEXT_STRING),
];
if (PHP_VERSION_ID < 70300) {
    $expected[] = new Word('EOT', null, 28, 5, PhpParser::CONTEXT_CODE);
}
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = <<<\'EOT\'
bbb
ccc
EOT;';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('EOT', null, 17, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 22, 3, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 26, 4, PhpParser::CONTEXT_STRING),
];
if (PHP_VERSION_ID < 70300) {
    $expected[] = new Word('EOT', null, 30, 5, PhpParser::CONTEXT_CODE);
}
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = "bbb $ccc ddd";';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 14, 2, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 19, 2, PhpParser::CONTEXT_CODE),
    new Word('ddd', null, 23, 2, PhpParser::CONTEXT_STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = "bbb {$ccc} ddd";';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 14, 2, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 20, 2, PhpParser::CONTEXT_CODE),
    new Word('ddd', null, 25, 2, PhpParser::CONTEXT_STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = "bbb {$ccc->ddd} eee";';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 14, 2, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 20, 2, PhpParser::CONTEXT_CODE),
    new Word('ddd', null, 25, 2, PhpParser::CONTEXT_CODE),
    new Word('eee', null, 30, 2, PhpParser::CONTEXT_STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$string = '<?php
$aaa = "bbb {$ccc[\'ddd\']} eee"';
$expected = [
    new Word('aaa', null,  7, 2, PhpParser::CONTEXT_CODE),
    new Word('bbb', null, 14, 2, PhpParser::CONTEXT_STRING),
    new Word('ccc', null, 20, 2, PhpParser::CONTEXT_CODE),
    new Word('ddd', null, 25, 2, PhpParser::CONTEXT_STRING),
    new Word('eee', null, 32, 2, PhpParser::CONTEXT_STRING),
];
$actual = $phpParser->parse($string);
Assert::equal($expected, $actual);


$html = '
aaa
bbb
<?php
$ccc = 1;
?>
ddd
eee';
$expected = [
    new Word('aaa', null,  1, 2, PhpParser::CONTEXT_HTML),
    new Word('bbb', null,  5, 3, PhpParser::CONTEXT_HTML),
    new Word('ccc', null, 16, 5, PhpParser::CONTEXT_CODE),
    new Word('ddd', null, 28, 7, PhpParser::CONTEXT_HTML),
    new Word('eee', null, 32, 8, PhpParser::CONTEXT_HTML),
];
$actual = $phpParser->parse($html);
Assert::equal($expected, $actual);


$halt = '<?php
__halt_compiler();
aaa
bbb';
$expected = [
    new Word('aaa', null, 25, 3, PhpParser::CONTEXT_DATA),
    new Word('bbb', null, 29, 4, PhpParser::CONTEXT_DATA),
];
$actual = $phpParser->parse($halt);
Assert::equal($expected, $actual);
