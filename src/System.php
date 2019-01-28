<?php declare(strict_types = 1);
/**
 * This file is part of the Dogma library (https://github.com/paranoiq/dogma)
 *
 * Copyright (c) 2012 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace Dogma\Tools;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;
use function strstr;
use function strtolower;

class System
{

    public static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' && strstr(strtolower(PHP_OS), 'win') !== false;
    }

}
