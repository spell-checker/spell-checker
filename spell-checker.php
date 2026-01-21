<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Application\Colors as C;
use Dogma\Application\Configurator;
use Dogma\Application\Console;
use Tracy\Debugger;

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
} elseif (file_exists(dirname(__DIR__, 2) . '/autoload.php')) {
    // run from other app
    require_once dirname(__DIR__, 2) . '/autoload.php';
    if (Debugger::$logDirectory === null) {
        $logDir = getcwd() . '/log';
        if (!is_dir($logDir)) {
            mkdir($logDir);
        }
        Debugger::$logDirectory = $logDir;
    }
} else {
    die('SpellChecker: Run `composer install` to install dependencies.');
}

$arguments = [
        'Configuration:',
    'config' =>         ['c', Configurator::VALUES, 'configuration files', 'paths'],
    'memoryLimit' =>    ['m', Configurator::VALUE, 'memory limit'],
    'use' =>            ['', Configurator::VALUES, 'configuration profiles to use', 'profiles'],
    'debug' =>          ['', Configurator::FLAG, 'show debug info'],
	'baseDir' =>        ['b', Configurator::VALUE, 'base directory for relative paths (all files)', 'path'],
        'File selection:',
	'filesBaseDir' =>   ['', Configurator::VALUE, 'base directory for relative paths (checked files)', 'path'],
    'files' =>          ['f', Configurator::VALUES, 'files to check', 'paths'],
    'directories' =>    ['d', Configurator::VALUES, 'directories to check', 'paths'],
    'extensions' =>     ['e', Configurator::VALUES, 'file extensions to check', 'extensions'],
        'Dictionaries:',
	'dictionariesBaseDir' => ['', Configurator::VALUE, 'base directory for relative paths (dictionaries)', 'path'],
	'dictionaryDirectories' => ['', Configurator::VALUES, 'paths to directories containing dictionaries', 'paths'],
    'dictionaries' =>   ['D', Configurator::VALUES, 'dictionaries to use on all files', 'list'],
    'dictionariesByFileName' => ['n', Configurator::VALUES, 'file name pattern -> list of dictionaries', 'map'],
    'dictionariesByFileExtension' => ['x', Configurator::VALUES, 'file extension -> list of dictionaries', 'map'],
    'dictionariesWithDiacritics' => ['', Configurator::VALUES, 'dictionaries containing words with diacritics', 'list'],
        'Other:',
    'localIgnores'      => ['', Configurator::VALUES, 'file name pattern -> list of locally ignored words', 'map'],
    'checkLocalIgnores' => ['', Configurator::FLAG, 'check if all local exceptions are used'],
    'checkDictionaryFiles' => ['', Configurator::FLAG, 'check configured dictionary file for unused word'],
    'dictionaryFilesToCheck' => ['', Configurator::VALUES, 'list of user dictionaries to check for unused words', 'names'],
    'short' =>          ['s', Configurator::FLAG, 'shorter output with only file and list of words'],
    'topWords' =>       ['t', Configurator::FLAG, 'output list of top misspelled words'],
    'maxErrors' =>      ['', Configurator::VALUE, 'maximum number of error before check stops', 'number'],
    'wordsParserExceptions' => ['', Configurator::VALUES, 'irregular words', 'words'],
    'ignoreUrls' =>     ['', Configurator::FLAG, 'ignore all words from URL addresses'],
    'ignoreEmails' =>   ['', Configurator::FLAG, 'ignore all words from email addresses'],
    'heuristics' =>     ['', Configurator::VALUES, 'class name pattern -> list of custom heuristics', 'paths'],
        'Help:',
    'help' =>           ['h', Configurator::FLAG_VALUE, 'show help', 'command'],
    'license' =>        ['', Configurator::FLAG, 'show license'],
        'CLI output:',
    'noColors' =>       ['C', Configurator::FLAG, 'without colors'],
    'noLogo' =>         ['L', Configurator::FLAG, 'without logo'],
];
$defaults = [
    'config' => [strtr(__DIR__, '\\', '/') . '/build/spell-checker.neon'],
    'maxErrors' => SpellChecker::DEFAULT_MAX_ERRORS,
    'wordsParserExceptions' => ['PHPUnit'],
    'ignoreUrls' => false,
    'ignoreEmails' => false,
];
$config = new Configurator($arguments, $defaults);
$config->loadCliArguments();

$console = new Console();
$console->debug = $config->debug;

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

if ($config->memoryLimit !== null) {
    if (!preg_match('#^\d+[kMG]?$#i', $config->memoryLimit)) {
        $console->writeLn(C::white(sprintf('Invalid memory limit format "%s".', $config->memoryLimit), C::RED));
        return 1;
    }
    if (ini_set('memory_limit', $config->memoryLimit) === false) {
        $console->writeLn(C::white(sprintf('Memory limit "%s" cannot be set.', $config->memoryLimit), C::RED));
        return 1;
    }
}

$application = new SpellCheckerApplication($console);
$application->run($config);

