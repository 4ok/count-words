<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../src/utils/autoload.php';

use classes\CountWords;

$input = __DIR__ . '/beyond-good-and-evil.txt';
$output = __DIR__ . '/result.csv';

$countWords = new CountWords($input, $output);