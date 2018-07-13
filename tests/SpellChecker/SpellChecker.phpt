<?php declare(strict_types = 1);

namespace SpellChecker;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Dictionary\DictionaryResolver;
use SpellChecker\Heuristic\DictionarySearch;
use SpellChecker\Parser\PhpParser;
use SpellChecker\Parser\PlainTextParser;
use SpellChecker\Parser\SimpleParserProvider;
use Tester\Assert;
use function key;

require __DIR__ . '/../bootstrap.php';

$phpParser = new PhpParser(new PlainTextParser());
$parsers = new SimpleParserProvider(['php' => $phpParser]);
$directory = '/../../vendor/spell-checker/dictionary-en';
$dictionaries = new DictionaryCollection([$directory], [], [], __DIR__);
$heuristic = new DictionarySearch($dictionaries);
$resolver = new DictionaryResolver(['en'], [], []);

$spellChecker = new SpellChecker($parsers, [$heuristic], $resolver);

$file = __DIR__ . '/SpellCheckerTestClass.php';
$result = $spellChecker->checkFiles([$file]);

Assert::same(1, $result->getFilesCount());
Assert::same([$file], $result->getFiles());

Assert::true($result->errorsFound());
Assert::same(1, $result->getErrorsCount());

$errors = $result->getErrors();
Assert::count(1, $errors);
Assert::same($file, key($errors));

/** @var \SpellChecker\Word[] $words */
$words = $result->getErrors()[$file];
Assert::count(1, $words);

$word = $words[0];
Assert::true($word instanceof Word);
Assert::same('hakusema', $word->word);
Assert::same(397, $word->position);
Assert::same(20, $word->rowNumber);
Assert::same('string', $word->context);
