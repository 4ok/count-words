<?php

namespace helpers;

class Fs
{
    public static function deleteDir($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!self::deleteDir("$dir/$item")) {
                return false;
            }
        }
    
        return rmdir($dir);
    }
}
