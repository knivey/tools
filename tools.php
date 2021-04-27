<?php
namespace knivey\tools;

/**
 * Recursively looks through a directory and builds an array of all the files in it
 * Omits hidden files and links
 * @param string $dir directory to search
 * @param string $extension only show files with this extension (case insensitive), null for no filtering
 * @return array
 * @throws \Exception
 */
function dirtree(string $dir, string $extension = "txt"): array {
    if(!is_dir($dir)) {
        throw new \Exception("Not a directory");
    }
    if($dir[-1] != '/') {
        $dir = "$dir/";
    }
    $tree = [];
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $name = $dir . $file;
            $type = filetype($name);
            if($file == '.' || $file == '..') {
                continue;
            }
            if($type == 'dir' && $file[0] != '.') {
                foreach(dirtree($name . '/') as $ent) {
                    $tree[] = $ent;
                }
            }
            if($extension != null)
                if($type == 'file' && $name[0] != '.' && strtolower($extension) == strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
                    $tree[] = $name;
                }
            else
                if($type == 'file' && $name[0] != '.') {
                    $tree[] = $name;
                }
        }
        closedir($dh);
    } else {
        throw new \Exception("Unable to opendir");
    }
    return $tree;
}

