<?php

namespace knivey\tools;

use Exception;
use function mb_str_split;

/**
 * Recursively looks through a directory and builds an array of all the files in it
 * Omits hidden files and links
 * @param string $dir directory to search
 * @param string $extension only show files with this extension (case-insensitive), null for no filtering
 * @return array
 * @throws Exception
 */
function dirtree(string $dir, string $extension = "txt"): array
{
    if (!is_dir($dir)) {
        throw new Exception("Not a directory");
    }
    if ($dir[-1] != '/') {
        $dir = "$dir/";
    }
    $tree = [];
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $name = $dir . $file;
            $type = filetype($name);
            if ($file == '.' || $file == '..') {
                continue;
            }
            if ($type == 'dir' && $file[0] != '.') {
                foreach (dirtree($name . '/') as $ent) {
                    $tree[] = $ent;
                }
            }
            if ($extension != null)
                if ($type == 'file' && $name[0] != '.' && strtolower($extension) == strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
                    $tree[] = $name;
                } else
                    if ($type == 'file' && $name[0] != '.') {
                        $tree[] = $name;
                    }
        }
        closedir($dh);
    } else {
        throw new Exception("Unable to opendir");
    }
    return $tree;
}

/**
 * Takes a string of byte data and converts it to an int
 * @param string $s
 * @return number
 */
function str2int(string $s): int
{
    $hex = hexdump($s);
    return (int)hexdec($hex);
}

/**
 * Turn Bytes size into human-readable format
 * @param int|float $size
 * @return string
 */
function convert(int|float $size): string
{
    //using float because it can hold big numbers even tho it doesn't make sense for byte values to have decimal
    $size = (float)$size;
    if ($size == 0)
        return "{$size}b";
    $neg = '';
    if ($size < 0) {
        $neg = '-';
        $size = abs($size);
    }
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return $neg . @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
}

/**
 * Reverse the order of data string
 * @param string $s
 * @return string
 */
function revbo(string $s): string
{
    $s = str_split($s);
    $s = array_reverse($s);
    return implode('', $s);
}

/**
 * Provides a printable hex string from a data string
 * @param string $s
 * @return string
 */
function hexdump(string $s): string
{
    $s = str_split($s);
    $out = '';
    foreach ($s as $c) {
        $hex = dechex(ord($c));
        if (strlen($hex) == 1) {
            $hex = '0' . $hex;
        }
        $hex = strtoupper($hex);
        $out .= "$hex ";
    }
    return trim($out);
}

/**
 * Searches $ar until it finds case-insensitive match for $key
 * and returns it, or NULL on fail.
 * @param int|string $key
 * @param array $ar
 * @return int|string|null
 */
function get_akey_nc(int|string $key, array $ar): int|string|null
{
    $keys = array_keys($ar);
    foreach ($keys as $k) {
        if (strtolower($key) == strtolower($k))
            return $k;
    }
    return null;
}

/**
 * Pad all elements of an array with spaces so they contain the same number of characters
 * Also will add one space to the existing max length
 * @param array $array
 * @return array
 */
function array_padding(array $array): array
{
    $pad = 0;
    for ($i = 0; $i < count($array); $i++) {
        if (strlen($array[$i]) - substr_count($array[$i], "\2") > $pad) {
            $pad = strlen($array[$i]) - substr_count($array[$i], "\2");
        }
    }
    for ($i = 0; $i < count($array); $i++) {
        if ($pad - strlen($array[$i]) + substr_count($array[$i], "\2") + 1 > 0) {
            $array[$i] = $array[$i] . str_repeat(' ', $pad - strlen($array[$i]) + substr_count($array[$i], "\2") + 1);
        }
    }
    return $array;
}

/**
 * Takes a 2D Array and pads it to make a nicely printable table.
 * The first dimension of the array is the rows, the second columns.
 * @param array $array
 * @return array
 */
function multi_array_padding(array $array): array
{
    //First dimension is rows second is cols
    $col = array();
    $cols = count($array[0]);
    $rows = count($array);
    for ($c = 0; $c < $cols; $c++) { // go through each col
        for ($r = 0; $r < $rows; $r++) { // go through each row
            $col[] = $array[$r][$c];
        }
        $col = array_padding($col);
        for ($r = 0; $r < $rows; $r++) { // go through each row again and reset it
            $array[$r][$c] = $col[$r];
        }
        $col = array();
    }
    return $array;
}

/**
 * Get the time including microseconds as a float
 * @return float
 * @codeCoverageIgnore
 */
function microtime_float(): float
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * This will make an argv array while treating "quoatable(\") arguments"
 * as one argument in the array. If there is an error it will return
 * a number indicating the position of the error. (ugly)
 * @param string $string
 * @return array|int
 */
function makeArgs(string $string): array|int
{
    /* 7/3/09 - Finished
     * take $string  and make args split by
     * spaces but including " support and \ support
     * then return as an array or a pos of error
     */
    $s = str_split($string);
    $skip = false;
    $pos = -1;
    $args = array();
    $inQuote = false;
    $lastChar = ' ';

    $curArg = 0;
    $Bskip = false;

    foreach ($s as $c) {
        $pos++;
        if ($Bskip) {
            $Bskip = false; // skip adding char too
            $lastChar = $c;
            continue;
        }
        if ($c == "\\") {
            $skip = true; //skip over next char
            $lastChar = $c;
            continue;
        }
        if ($skip) {
            $skip = false; // only skip one char
            $lastChar = $c;
            if (!array_key_exists($curArg, $args)) {
                $args[$curArg] = '';
            }
            $args[$curArg] .= $c;
            continue;
        }

        if ($c == '"') {
            if (!$inQuote) {
                if ($lastChar != ' ') { //Quote should only come at begin of arg
                    return $pos + 1; // Error at pos
                }
                $inQuote = true;
            } else {
                if (isset($string[$pos + 1]) && $string[$pos + 1] != ' ') {
                    //only end or space should follow end quote
                    return $pos + 1;
                }
                if ($lastChar == '"' && !isset($args[$curArg])) {
                    $args[$curArg] = '';
                }
                $Bskip = true; //skip next space
                $inQuote = false;
                $curArg++;
            }
            $lastChar = $c;
            continue;
        }
        if (!$inQuote && $c == ' ') {
            if ($lastChar != ' ') {
                $curArg++;
            }
            $lastChar = $c;
            continue;
        }
        $lastChar = $c;
        if (!array_key_exists($curArg, $args)) {
            $args[$curArg] = '';
        }
        $args[$curArg] .= $c;
    }
    return $args;
}

function isDigit(string $str): bool
{
    if ($str == "")
        return false;
    foreach (mb_str_split($str) as $c) {
        if (strlen($c) > 1)
            return false;
        if (ord($c) < 48 || ord($c) > 57) {
            return false;
        }
    }
    return true;
}

/**
 * Escapes what could be used as back references \ \0 $1 etc...
 * useful for doing preg_replace but not wanting any back references
 */
function escapeRegexReplace(string $str): string
{
    $out = '';
    $str = mb_str_split($str);
    for ($i = 0; $i < count($str); $i++) {
        if (($str[$i] == '\\' || $str[$i] == '$') && isset($str[$i + 1])) {
            if ($str[$i + 1] == '\\') {
                $out .= '\\';
            }
            if (isDigit($str[$i + 1])) {
                $out .= '\\';
            }
        }
        $out .= $str[$i];
    }
    return $out;
}

/**
 * Converts a glob to a regular expression (including delimiter)
 * you may want to append flags to the end of returned regex like i for case-insensitive
 * @param string $glob
 * @param string $delimiter
 * @param bool $anchor If true will add ^ and $ for start/end
 * @return string
 */
function globToRegex(string $glob, string $delimiter = '/', bool $anchor = true): string
{
    $out = '';
    $i = 0;
    while ($i < strlen($glob)) {
        $nextc = strcspn($glob, '*?', $i);
        $out .= preg_quote(substr($glob, $i, $nextc), $delimiter);
        if ($nextc + $i == strlen($glob))
            break;
        if ($glob[$nextc + $i] == '?')
            $out .= '.';
        if ($glob[$nextc + $i] == '*')
            $out .= '.*';
        $i += $nextc + 1;
    }
    if ($anchor)
        return "{$delimiter}^{$out}\${$delimiter}";
    return "{$delimiter}{$out}{$delimiter}";
}