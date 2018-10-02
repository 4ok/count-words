<?php

namespace classes\CountWords;

use interfaces\Comparable;

class Word implements Comparable
{
    private $data;

    public function __construct($word, $num, $fileIndex, $handle)
    {
        $this->data = [
            'word' => $word,
            'num' => $num,
            'fileIndex' => $fileIndex,
            'handle' => $handle,
        ];
    }

    public function compareTo($value)
    {
        $result = strcmp($value->word, $this->word);

        if ($result === 0) {
            $result = strcmp($value->fileIndex, $this->fileIndexa);
        }

        return $result;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
    }
}