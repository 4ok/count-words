<?php

spl_autoload_register('Autoload::init');

class Autoload
{
    const SRC_DIR = __DIR__ . '/../';

    private static $requiredFiles = [];

    static function init($class) {
        $prefix = self::SRC_DIR . str_replace('\\', '/', $class);
        $files = [
            "$prefix.php",
            "$prefix/Index.php",
        ];

        foreach ($files as $file) {

            if (in_array($file, self::$requiredFiles)) {
                return;
            }

            if (file_exists($file)) {
                self::$requiredFiles[] = $file;
                require_once $file;

                return;
            }
        }

        throw new Exception("Class \"$class\" not found");
    }
}
