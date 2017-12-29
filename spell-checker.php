<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Configurator;
use Dogma\Tools\Console;
use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Dictionary\DictionaryResolver;
use SpellChecker\Heuristic\Base64ImageDetector;
use SpellChecker\Heuristic\CssUnitsDetector;
use SpellChecker\Heuristic\DictionarySearch;
use SpellChecker\Heuristic\EscapeSequenceDetector;
use SpellChecker\Heuristic\FileNameDetector;
use SpellChecker\Heuristic\GarbageDetector;
use SpellChecker\Heuristic\PrintfDetector;
use SpellChecker\Heuristic\SqlTableShortcutDetector;
use SpellChecker\Heuristic\IdentifiersDetector;
use Tracy\Debugger;

require_once __DIR__ . '/src/Colors.php';
require_once __DIR__ . '/src/Console.php';
$console = new Console();

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // run separately
    require_once __DIR__ . '/vendor/autoload.php';

    $logDir = __DIR__ . '/log';
    if (!is_dir($logDir)) {
        mkdir($logDir);
    }
    Debugger::enable(Debugger::DEVELOPMENT, $logDir);
    Debugger::$maxDepth = 8;
    Debugger::$maxLength = 1000;
    Debugger::$showLocation = true;
} elseif (file_exists(dirname(dirname(__DIR__)) . '/autoload.php')) {
    // run from other app
    require_once dirname(dirname(__DIR__)) . '/autoload.php';
    if (Debugger::$logDirectory === null) {
        $logDir = getcwd() . '/log';
        if (!is_dir($logDir)) {
            mkdir($logDir);
        }
        Debugger::$logDirectory = $logDir;
    }
} else {
    $console->write(C::lcyan('Spell Checker'))->ln(2);
    $console->write(C::white('Run `composer install` to install dependencies.', C::RED));
    die();
}

$arguments = [
        'Configuration:',
    'config' =>         ['c', Configurator::VALUES, 'configuration files', 'paths'],
    'use' =>            ['', Configurator::VALUES, 'configuration profiles to use', 'profiles'],
    'baseDir' =>        ['b', Configurator::VALUE, 'base directory for relative paths', 'path'],
    'files' =>          ['f', Configurator::VALUES, 'files to check', 'paths'],
    'directories' =>    ['d', Configurator::VALUES, 'directories to check', 'paths'],
    'extensions' =>     ['e', Configurator::VALUES, 'file extensions to check', 'extensions'],
    'excludes' =>       ['E', Configurator::VALUES, 'file name patterns to exclude', 'patterns'],
    'fileContexts' =>   ['', Configurator::VALUES],
    'contexts' =>       ['', Configurator::VALUES],
    'dictionaries' =>   ['', Configurator::VALUES],
    'dictionaryDirectories' => ['D', Configurator::VALUES, 'paths to directories containing dictionaries', 'paths'],
    'dictionariesByFileExtension' => ['', Configurator::VALUES],
    'dictionariesWithDiacritics' => ['', Configurator::VALUES, 'dictionaries containing words with diacritics', 'dictionaries'],
    'checkDictionaries' => ['', Configurator::VALUES, 'list of user dictionaries to check for unused words', 'dictionaries'],
    'wordsParserExceptions' => ['', Configurator::VALUES, 'irregular words', 'words'],
        'Help:',
    'help' =>           ['h', Configurator::FLAG_VALUE, 'show help', 'command'],
    'license' =>        ['', Configurator::FLAG, 'show license'],
        'CLI output:',
    'topWords' =>       ['t', Configurator::FLAG, 'output list of top misspelled words'],
    'noColors' =>       ['C', Configurator::FLAG, 'without colors'],
    'noLogo' =>         ['L', Configurator::FLAG, 'without logo'],
];
$defaults = [
    'config' => [strtr(__DIR__, '\\', '/') . '/build/spell-checker.neon'],
    'wordsParserExceptions' => ['PHPUnit'],
];
$config = new Configurator($arguments, $defaults);
$config->loadCliArguments();

if ($config->noColors) {
    C::$off = true;
}

if (!$config->noLogo) {
    $console->writeLn(C::lgreen("             _ _        _           _           "));
    $console->writeLn(C::lgreen(" ___ ___ ___| | |   ___| |_ ___ ___| |_ ___ ___ "));
    $console->writeLn(C::lgreen("|_ -| . | -_| | |  |  _|   | -_|  _| '_| -_|  _|"));
    $console->writeLn(C::lgreen("|___|  _|___|_|_|  |___|_|_|___|___|_,_|___|_|  " . C::lcyan(' by @paranoiq')));
    $console->writeLn(C::lgreen("    |_|                                         "));
    $console->ln();
}

if ($config->help === true || (!$config->hasValues() && (!$config->config))) {
    $console->write('Usage: php spell-checker.php [options]')->ln(2);
    $console->write($config->renderHelp());
    exit;
} elseif ($config->license || $config->help === 'license') {
    $console->writeFile(__DIR__ . '/license.md');
    exit;
}

foreach ($config->config as $path) {
    $config->loadConfig($path);
}

try {
    $files = (new FileFinder())->findFilesByConfig($config);
    $resolver = new DictionaryResolver(
        $config->fileContexts ?? [],
        $config->contexts ?? [],
        $config->dictionariesByFileExtension ?? []
    );
    $dictionaries = new DictionaryCollection(
        $config->dictionaryDirectories ?? [],
        $config->dictionariesWithDiacritics ?? [],
        $config->checkDictionaries ?? [],
        $config->baseDir
    );
    $wordsParser = new WordsParser(
        $config->wordsParserExceptions ?? []
    );
    $heuristics = [
        new DictionarySearch($dictionaries),
        new CssUnitsDetector(),
        new PrintfDetector(),
        new EscapeSequenceDetector(),
        new SqlTableShortcutDetector(),
        new IdentifiersDetector($dictionaries),
        new FileNameDetector($dictionaries),
        new GarbageDetector(),
        new Base64ImageDetector(),
    ];
    $spellChecker = new SpellChecker($wordsParser, $heuristics, $resolver, $dictionaries, $config->baseDir);

    $startTime = microtime(true);
    $result = $spellChecker->checkFiles($files, function (string $fileName) use ($console) {
        $console->write('.');
        return true;
    });
    $totalTime = microtime(true) - $startTime;
    $console->writeLn(' (', number_format($totalTime, 3), 's)');

    $console->ln(2);
    Console::switchTerminalToUtf8();

    $formatter = new ResultFormatter($resolver);
    $console->writeLn($formatter->summarize($result));
    if ($result->errorsFound()) {
        if ($config->topWords) {
            //$console->ln()->write($formatter->formatFilesList($result->getFiles()));
            //$console->ln()->write($formatter->formatTopBlocksByContext($result));
            $console->ln()->write($formatter->formatErrors($result));
        } else {
            $console->ln()->write($formatter->formatErrors($result));
        }
    }
    if ($config->checkDictionaries) {
        foreach ($dictionaries->getDictionaries() as $name => $dictionary) {
            if (!$dictionary->isChecked()) {
                continue;
            }
            $unusedWords = $dictionary->getUnusedWords();
            if ($unusedWords !== []) {
                $console->writeLn(C::red('Unused words in dictionary "' . $name . '"'));
                $console->writeLn(implode(', ', $unusedWords));
            }
        }
    }

    if ($result->errorsFound()) {
        exit(1);
    }
} catch (\SpellChecker\FileSearchNotConfiguredException $e) {
    $console->writeLn(C::red('Nothing to check. Configure directories or files.'));
    exit(1);
} catch (\Throwable $e) {
    $console->ln()->writeLn(C::white(sprintf('Error occurred while spell-checking: %s', $e->getMessage()), C::RED));
    if (class_exists(Debugger::class)) {
        Debugger::log($e);
        exit(1);
    } else {
        throw $e;
    }
}
$console->ln();
