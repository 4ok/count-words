<?php

namespace classes\CountWords;

use SplPriorityQueue;

class WordsPriorityQueue extends SplPriorityQueue 
{
    public function compare($a, $b) 
    {
        return $a->compareTo($b);
    } 
} 