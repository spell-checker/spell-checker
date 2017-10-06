<?php

$syntax = "
php

# keywords
__halt_compiler
abstract
and
array
as
break
callable
case
catch
class
clone
const
continue
declare
default
die
do
echo
else
elseif
empty
enddeclare
endfor
endforeach
endif
endswitch
endwhile
eval
exit
extends
final
finally
for
foreach
function
global
goto
if
implements
include
include_once
instanceof
insteadof
interface
isset
list
namespace
new
or
print
private
protected
public
require
require_once
return
static
switch
throw
trait
try
unset
use
var
while
xor
yield

# magic constants
__CLASS__
__DIR__
__FILE__
__FUNCTION__
__LINE__
__METHOD__
__NAMESPACE__ 
__TRAIT__

# types
bool
boolean
callable
double
false
float
int
integer
iterable
null
object
real
resource
string
true

# globals
GLOBALS
argc
argv
stderr
stdin
stdout

";

$coreExtensions = [
    'bcmath',
    'calendar',
    'cgi-fcgi',
    'Core',
    'ctype',
    'date',
    'dom',
    'filter',
    'hash',
    'iconv',
    'json',
    'libxml',
    'mcrypt',
    'mysqlnd',
    'pcre',
    'PDO',
    'Phar',
    'readline',
    'Reflection',
    'session',
    'SimpleXML',
    'SPL',
    'standard',
    'tokenizer',
    'wddx',
    'xml',
    'xmlreader',
    'xmlwriter',
    'zip',
    'zlib',
];

$core = fopen(dirname(__DIR__) . '/dictionaries/php.dic', 'wb');
$ext = fopen(dirname(__DIR__) . '/dictionaries/php-ext.dic', 'wb');
fputs($core, $syntax);

foreach (get_loaded_extensions() as $extName) {
    $file = in_array($extName, $coreExtensions) ? $core : $ext;

    $extension = new \ReflectionExtension($extName);
    fputs($file, "\n# extension: $extName");

    $constants = $extension->getConstants();
    if ($constants) {
        fputs($file, "\n# constants\n");
        foreach ($constants as $name => $value) {
            fputs($file, $name . "\n");
        }
    }

    $functions = $extension->getFunctions();
    if ($functions) {
        fputs($file, "\n# functions\n");
        foreach ($functions as $function) {
            fputs($file, $function->name . "\n");
        }
    }

    $classes = $extension->getClasses();
    if ($classes) {
        fputs($file, "\n# classes");
        foreach ($classes as $class) {
            fputs($file, "\n" . $class->name . "\n");
            foreach ($class->getConstants() as $name => $value) {
                fputs($file, $name . "\n");
            }
            foreach ($class->getMethods() as $method) {
                if ($method->isPrivate()) {
                    continue;
                }
                fputs($file, $method->name . "\n");
            }
            foreach ($class->getProperties() as $property) {
                if ($property->isPrivate()) {
                    continue;
                }
                fputs($file, $property->name . "\n");
            }
        }
    }
}
