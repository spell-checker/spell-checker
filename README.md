
# Spell-Checker
Multi-language code and text spell checking tool for PHP

Spell-checker can help you find typos in your texts, translations and code.
It is highly configurable, can use multiple languages at once and cope with escaping, encoding, identifiers without diacritics and other edge cases.


## How it works
Spell checker parses given files into words and blocks and tries to find them in configured dictionaries or rule them out by other heuristics.

For non-native languages, each word-like string is sliced to individual words.
For example `PHPUnit_Framework_MockObject` is parsed to `PHPUnit`, `Framework`, `Mock` and `Object` words.
Each word can be matched against dictionaries, or the whole block can be matched.

If not found in dictionaries as is, lower case version is searched for.
- when lowercase version is specified in dictionary, all case variants can match that word. eg `foo` will match `foo`, `Foo`, `FOO` and others
- when a non-lowercase version is specified in dictionary, it will match only the exact same word. eg `Foo` will only match the same thing

For some contexts like URLs, file names and other identifiers, word can be stripped of accents and searched for in dictionaries without diacritics.

When not found, other heuristics are used to rule out obvious false positives:
- detecting string escape sequences and encodings
- detecting printf escape sequences
- detecting regular expressions
- detecting table name shortcuts in SQL code
- detecting CSS values
- skipping random lumps of characters that look like passwords or tokens
- skipping base64 encoded images

You can add custom heuristics, more below.

If word is not found in dictionaries nor matched by any of these heuristics, it is reported as a typo.


## Prerequisites

Runs on PHP >= 7.2



## Installation

Install spell-checker to your existing PHP project using [Composer](https://getcomposer.org/):
```
composer require --dev spell-checker/spell-checker
```

For separate installation first download or clone the package from [Github](https://github.com/spell-checker/spell-checker/releases),
than install dependencies with [Composer](https://getcomposer.org/) by running following command in its main directory:
```
composer install
```

You may want to install some external dictionaries for native languages. eg
```
composer require --dev spell-checker/dictionary-cs
```

## Running

### Separately
```
php spell-checker.php -c path/to/config.neon -f some-file.txt,other-file.txt
```
Can be ran without arguments when config file is in default location.

### When installed in your project by Composer
```
vendor/bin/spell-checker ...
```

### From your build tool
Depends on what you are using and on your configuration. Example for Phing configuration bellow:
```
bin/phing spell-check
```


## Configuration

Most of configuration options may be specified from command line and from config file.
The only two exceptions are `dictionariesByFileExtension` and `dictionariesByFileExtension` which can for now only be used in configuration file.

See help for all configuration options:
```
php spell-checker.php -h
```

See [Neon](https://ne-on.org/) for configuration files syntax.

Use `--config <path>` or `-c <path>` for loading configuration file.

### Selecting files to check
You may provide either the list of files to check or list of directories (and file extensions) or both of them.
If you specify both files and directories the files are filtered and only specified files from specified directories are checked.

`--files <list-of-files>` or `-f <list>` - list of file paths separated by comma (`,`). Paths may be either fully specified or relative.

`--directories <list-of-dirs>` or `-d <list>` - list of directory paths separated by comma (`,`). Paths may be either fully specified or relative.

`--extensions <list-of-ext>` or `-e <list>` - list of file extensions separated by comma (`,`)

`--baseDir <path>` or `-b <path>` - determines the base directory for relative paths

### Specifying dictionaries to use
Dictionary files are not configured directly. You can specify a dictionary file prefix and all files with that prefix will be used for this language.
For example for language `cs` files `cs.dic`, `cs.dia`, `cs-custom.dic`, `cs-municipalities.dic` and other will be loaded when found.

`--dictionaries <list-of-dictionaries>` or `-D <list>` - these languages will be used for all checked files

`--dictionariesByFileName <map-of-file-masks-to-dictionaries>` or `-n <map>` - dictionaries will be used for files matching the pattern. only first match is used.
- syntax: `file-pattern: lang1 lang2 ...` - file pattern is followed by a colon and then the list of languages separated by a space
- file pattern is a regular expression with two simplifications to make writing file paths more simple:
    - `.` is always escaped, so it matches only the `.` character
    - `*` is translated to `.*`, so it has same meaning as in glob patterns
- use `skip` keyword as a dictionary name to skip checking the file
- prefix dictionary name with `*` to always use it in mode without diacritics (not only for identifiers)

Example:
```
*-sk.html: en sk
*.html: en cs
*routes.conf: en *cs
*local.conf: skip
```

`--dictionariesByFileExtension <map-of-file-extensions-to-dictionaries>` or `-x <map>` - dictionaries will be used for all files with given extension
- syntax: `extension: foo bar` - file pattern is followed by a colon and then the list of dictionaries separated by a space
- if file is skipped in `dictionariesByFileName` configuration, it is not checked at all and `dictionariesByFileExtension` does not affect that

Example:
```
html: html svg code
js: js html code
latte: latte php js html svg code
neon: neon php code
php: php html svg mysql57 code
sql: mysql57 code
```

`--dictionariesWithDiacritics <list-of-dictionaries>` - list of dictionaries, that can be used without diacritics. `.dia` files are automatically loaded for them if found

`--dictionaryDirectories <list-of-paths>` - list of paths where to look for `.dic` and `.dia` files. also affected by `--baseDir` setting

### Checking unused exceptions

`--checkLocalIgnores` - turns on checking of local ignores in comments. unused ignored words will be reported as errors

`--checkDictionaryFiles` - turns on checking of unused words in custom dictionary files when configured

`--dictionaryFilesToCheck <list-of-file-names>` - receives list of file names (not paths, nor dictionary prefixes) to check for unused words

### Other settings

`--memoryLimit <size>` - set memory limit. eg `512M` or `1G` etc.

`--wordsParserExceptions <list-of-words>` - list of irregular words, that cannot be parsed according to usual naming standards
- for example word `PHPUnit` is not camel case and cannot be parsed to `php` and `unit` as it should be. instead is split to `P`, `H`, `P` and `Unit`. When you specify it as a parser exception it will be kept as is.



## Starting on a big code-base

When you start checking a big project, you will probably get thousands or tens of thousands of errors on the first run, most of them being false positives.
Do not let this fact discourage you. There are several ways to cope with this problem:

#### a) Adding only some directories or some file types at a time
This way you can decide how much time you want to spent fixing errors and false positive each time.
When you have some time, just add a new directory to the configuration, fix bugs and commit the changes.

#### b) Only checking the changed files
You can configure your build tools to only check changed files. This way you can progressively improve the state of your project one piece at a time.
See example in next section.

Number of false positives will fall down fast, because for global exceptions you only need to add them to your dictionaries one time to fix many occurrences of the same error.

On a large project i wrote spell-checker for (~1.000.000 lines of code) it took several days to completely clean the code base, fix about 1500 typos and add about 3000 exceptions to custom dictionaries.



## Dealing with false positives

Provided dictionary files are not complete and obviously do not contain common words in all shapes, names, technical terms and such.
There are two options for dealing with false positives. When you encounter a word which you know is not a typo, you can:

#### a) Add it to a custom dictionary file
For commonly used words, you may want to add these to your custom dictionary files.
Best way to organize custom dictionaries is to create one dictionary for each language.
Prefix the files with the shortcut of the language to be automatically loaded with it and configure dictionary directories appropriately.

Examples:
- `build/spell-checker/en-custom.dic` - for exceptions your texts in english
- `build/spell-checker/cs-custom.dic` - for exceptions your texts in czech
- `build/spell-checker/code-custom.dic` - for technical terms and other exceptions across many file types and languages

#### b) Implement custom heuristics
As already mentioned, heuristics help to filter out false positive cases. To add a custom heuristic, you need to create a class that implements `SpellChecker\Heuristic\Heuristic`. 
The class may require a `SpellChecker\Dictionary\DictionaryCollection` object in the constructor.
For more clarity see build in heuristics in `SpellChecker\Heuristic` namespace.

Heuristics need to be added to the config:
```
heuristics:
  - MyCustomHeuristicDetector
```

#### c) Add it to local ignore list in the same file as the word was found
Just use comment in given language and a directive `spell-check-ignore:` followed by the list of ignored words. This comment can be anywhere in the file, but only once, and can not be divided to more rows.

Examples:
- `// spell-check-ignore: foo bar` for PHP, JS, Java, C...
- `# spell-check-ignore: foo bar` for Python, Shell...
- `-- spell-check-ignore: foo bar` for SQL
- `<!-- spell-check-ignore: foo bar -->` for HTML and XML
- `{* spell-check-ignore: foo bar *}` for Latte


For some languages where comments are not supported (like `json` or `md`) you are obviously stuck only with the previous possibilities.



## Wiring spell-checker to your CI

### Example configuration for Phing build tool

```
<target name="spell-check">
    <exec executable="${path.spell-checker.executable}" logoutput="true" passthru="true" checkreturn="true">
        <arg line="--config ${path.spell-checker.config}"/>
        <arg line="--checkDictionaryFiles"/>
        <arg line="--memoryLimit 1024M"/>
    </exec>
</target>
```

### Checking only changed files when using version control
When you give both list of files (via command line) and list of directories (from config file) to spell-checker, only the listed files from given directories will be checked (intersect).

Do not run `--checkDictionaryFiles` in this case, since you are not checking all files.

```
<target name="spell-check-fast">
    <task-to-get-list-of-changed-files mask="*.*" fileSeparator="," outputProperty="files.changed" />

    <if>
        <not><equals arg1="${files.changed}" arg2="" /></not>
        <then>
            <exec executable="${path.spell-checker.executable}" logoutput="true" passthru="true" checkreturn="true">
                <arg line="--config ${path.spell-checker.config}"/>
                <arg line="--files '${files.changed}'"/>
                <arg line="--memoryLimit 1024M"/>
            </exec>
        </then>
    </if>
</target>
```



## Dictionaries

There are two dictionary file types
- `.dic` contains words including diacritics if given language has them
- `.dia` contains words with diacritics stripped from them. this is used for checking urls, file names and other types of identifiers

If a `.dia` file is not provided and dictionary is configured to be used without diacritics, version without diacritics is generated when loading the dictionary,
but stripping diacritics on the fly may be quite slow (10-20s) for big dictionaries with hundreds of thousands of words.

Dictionary format is very simple. Just use bare words divided by new lines (`\n`).
You can use comments starting with hash character - `#`. Comments cannot follow a word. Each word must be alone on its own row, without any comments or whitespace.

Tip: If you are using PhpStorm, use `ini` format to display the `.dic` and `.dia` files - it will highlight any duplicities in file.

### Included non-native language dictionary files so far:
- *country-codes.dic* - ISO country codes in 2 and 3 letter form
- *css.dic* - CSS attributes and values
- *gettext.dic* - Gettext keywords
- *html.dic* - HTML tags, attributes, values
- *js.dic* - JS keywords, functions, properties...
- *latte.dic* - Latte templating language macros
- *mysql57.dic* - MySQL 5.7 keywords, routines, schema objects, variables...
- *neon.dic* - Neon configuration language keywords
- *php.dic* - PHP keywords and core PHP extensions
- *php-ext.dic* - Non-core PHP extensions
- *php-config.dic* - PHP configuration directives
- *svg.dic* - SVG tags, attributes, values

### External native language dictionaries:
- https://github.com/spell-checker/dictionary-en - English dictionary
- https://github.com/spell-checker/dictionary-es - Spanish dictionary
- https://github.com/spell-checker/dictionary-cs - Czech dictionary (including no-accents version)
- https://github.com/spell-checker/dictionary-sk - Slovak dictionary (including no-accents version)



## Known issues
- words with `'` (eg: `don't`, `isn't`) in them are not checked as single word, but split into two parts and checked separately
- the same stands for words with `-` in them (eg: `e-mail`)
