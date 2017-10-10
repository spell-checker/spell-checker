<?php

namespace SpellChecker;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Configurator;
use Dogma\Tools\Console;
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
    $logDir = __DIR__ . '/log';
    if (!is_dir($logDir)) {
        mkdir($logDir);
    }
    Debugger::$logDirectory = $logDir;
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
    'filePatterns' =>   ['', Configurator::VALUES],
    'contexts' =>       ['', Configurator::VALUES],
    'dictionaries' =>   ['', Configurator::VALUES],
        'Help:',
    'help' =>           ['', Configurator::FLAG_VALUE, 'show help', 'command'],
    'license' =>        ['', Configurator::FLAG, 'show license'],
        'CLI output:',
    'noColors' =>       ['C', Configurator::FLAG, 'without colors'],
];
$defaults = [
    'config' => [strtr(__DIR__, '\\', '/') . '/config.neon'],
];
$config = new Configurator($arguments, $defaults);
$config->loadCliArguments();

if ($config->noColors) {
    C::$off = true;
}

$created = C::lcyan('Created by @paranoiq 2017');
$console->writeLn(C::lgreen("             _ _        _           _           "));
$console->writeLn(C::lgreen(" ___ ___ ___| | |   ___| |_ ___ ___| |_ ___ ___ "));
$console->writeLn(C::lgreen("|_ -| . | -_| | |  |  _|   | -_|  _| '_| -_|  _|"));
$console->writeLn(C::lgreen("|___|  _|___|_|_|  |___|_|_|___|___|_,_|___|_|  "));
$console->writeLn(C::lgreen("    |_|             ") . $created)->ln();

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
    $resolver = new DictionaryResolver($config->filePatterns, $config->contexts);
    $dictionaries = new DictionaryCollection($config->dictionaries, $config->baseDir);
    $spellChecker = new SpellChecker(new WordsParser(), $resolver, $dictionaries, $config->baseDir);

    $fileCallback = function () use ($console) {
        $console->write('.');
        return true;
    };
    if ($config->dictionaries) {
        $errors = $spellChecker->checkDirectories($config->directories, $fileCallback);
    } elseif ($config->files) {
        $errors = $spellChecker->checkFiles($config->files, $fileCallback);
    } else {
        $console->writeLn(C::red('Nothing to check. Configure directories or files.'));
        exit(1);
    }

    $console->ln(2);
    Console::switchTerminalToUtf8();

    //echo $dictionaries->info();
    if (count($errors) > 0) {
        $console->ln()->writeLn(C::white('Some spelling errors found.', C::RED));
        dump($errors);
        exit(1);
    }
} catch (\Throwable $e) {
    $console->ln()->writeLn(C::white('Error occurred while spell-checking.', C::RED));
    if (class_exists(Debugger::class)) {
        Debugger::log($e);
        exit(1);
    } else {
        throw $e;
    }
}
$console->ln();
