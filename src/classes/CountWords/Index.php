<?php

namespace classes;

use Exception;

use classes\CountWords\{
    Word,
    WordsPriorityQueue
};

use helpers\Fs;

class CountWords
{
    const MAX_WORDS_IN_MEMORY = 1000;
    const READ_CHUNK_LENGTH = 4096;
    const FILE_NAME_SEPARATOR = '.';
    const TEMP_DIRNAME = 'tmp';
    const DATA_SEPARATOR = ';';
    const OUTPUT_HEADERS = [
        'Word',
        'Number',
    ];

    private $file;

    public function __construct($input, $output)
    {
        if (!file_exists($input)) {
            throw new Exception("File \"$input\" not found");
        }

        $this->input = $input;
        $this->output = $output;
        $this->outputDir = dirname($output);
        $this->tmpDir = $this->outputDir . '/' . self::TEMP_DIRNAME;

        Fs::deleteDir($this->tmpDir);
        mkdir($this->tmpDir, 0700, true);

        $this->writeResult();

        Fs::deleteDir($this->tmpDir);
    }

    private function writeResult() {
        $lastFileIndex = $this->generateNumWordsFiles();
        $step = self::MAX_WORDS_IN_MEMORY;
        $reduceIndex = 0;

        while ($lastFileIndex > $step) {
            $li = floor($lastFileIndex / $step);

            for ($i = 0; $i <= $li; $i++) {
                $fromFileIndex = $i * $step;
                $toFileIndex = $firstFileIndex + $step - 1;

                if ($toFileIndex > $lastFileIndex) {
                    $toFileIndex = $lastFileIndex;
                }

                $outputFileName = ($reduceIndex + 1) . self::FILE_NAME_SEPARATOR . $i;
                $output = $this->getTmpFilePath($outputFileName);
                $numWordsQueue = $this->reduceNumWordsFiles($reduceIndex, $fromFileIndex, $toFileIndex, $output);
            }

            $lastFileIndex = $li;
            $reduceIndex++;
        };

        $numWordsQueue = $this->reduceNumWordsFiles($reduceIndex, 0, $lastFileIndex, $this->output, self::OUTPUT_HEADERS);
    }

    private function reduceNumWordsFiles($reduceIndex, $firstFileIndex, $lastFileIndex, $output, $header = null)
    {
        $wordsQueue = new WordsPriorityQueue();
        
        for ($fileIndex = $firstFileIndex; $fileIndex <= $lastFileIndex; $fileIndex++) {
            $fileName = $reduceIndex . self::FILE_NAME_SEPARATOR . $fileIndex;
            $filePath = $this->getTmpFilePath($fileName);
            $handle = fopen($filePath, 'r');
            $word = $this->getNextWordFromFile($handle, $fileIndex);

            if ($word) {
                $wordsQueue->insert($word, $word);
            } else {
                fclose($handle);
            }
        }

        $this->writeWords($wordsQueue, $output, $header);
    }

    private function getNextWordFromFile($handle, $fileIndex)
    {
        $line = fgets($handle);

        if ($line === false) {
            return null;
        }

        list($word, $num) = explode(self::DATA_SEPARATOR, trim($line), 2);

        return new Word(
            $word,
            (int) $num,
            $fileIndex,
            $handle
        );
    }

    private function writeWords($wordsQueue, $output, $headers) {
        $prevWord = null;
        $wordNum = 0;

        $handle = fopen($output, 'w');

        $writeRow = function ($currWord, $currWordNum) use (&$handle) {
            $line = $currWord . self::DATA_SEPARATOR . $currWordNum . PHP_EOL;
            fputs($handle, $line);
        };

        if ($headers) {
            call_user_func_array($writeRow, $headers);
        }

        while ($wordsQueue->valid()) {
            $word = $wordsQueue->extract();

            if ($prevWord) {

                if ($word->word === $prevWord->word) {
                    $wordNum += $word->num;
                } else {
                    $writeRow($prevWord->word, $wordNum);

                    $wordNum = $word->num;
                }
            } else {
                $wordNum = $word->num;
            }

            $prevWord = $word;
            $nextWordFromFile = $this->getNextWordFromFile($word->handle, $word->fileIndex);

            if ($nextWordFromFile) {
                $wordsQueue->insert($nextWordFromFile, $nextWordFromFile);
            } else {
                fclose($word->handle);
            }
        }

        $writeRow($prevWord->word, $wordNum);
    }

    private function generateNumWordsFiles()
    {
        $fileIndex = -1;
        $mapNumWords = [];

        $handle = fopen($this->input, 'r');
        
        $sortAndDumpToFile = function() use (&$fileIndex, &$mapNumWords) {
            $fileIndex++;

            ksort($mapNumWords);

            $this->dumpToFile($fileIndex, $mapNumWords);

            $mapNumWords = [];
        };

        $getWord = function ($word) {
            $w = '(?:[^a-zа-я])+';
            $word = preg_replace("#(^$w|$w$)#i", '', $word);
                
            if (preg_match('#^[a-zа-я]#', $word)) {
                return strtolower($word);
            }
        };

        while (($line = fgets($handle, self::READ_CHUNK_LENGTH)) !== false) {
            $words = preg_split('#\s+#', trim($line));

            foreach ($words as &$word) {
                $word = $getWord($word);
                
                if (!$word) {
                    continue;
                }
                
                if (!isset($mapNumWords[$word])) {
                    $mapNumWords[$word] = 0;
                }

                $mapNumWords[$word]++;

                if (count($mapNumWords) >= self::MAX_WORDS_IN_MEMORY) {
                    $sortAndDumpToFile();
                }
            }

            unset($word);
        }

        if (count($mapNumWords) > 0) {
            $sortAndDumpToFile();
        }

        if (!feof($handle)) {
            throw new Exception("Read error the file \"$this->file\"");
        }
        
        fclose($handle);

        return $fileIndex;
    }

    private function dumpToFile($fileIndex, $mapNumWords)
    {
        $fileName = '0' . self::FILE_NAME_SEPARATOR . $fileIndex;
        $filePath = $this->getTmpFilePath($fileName);
        $content = [];

        foreach ($mapNumWords as $word => $num) {
            $content[] = $word . self::DATA_SEPARATOR . $num;
        }

        $content = implode(PHP_EOL, $content);

        file_put_contents($filePath, $content);
    }

    private function getTmpFilePath($fileName)
    {
        return $this->tmpDir . "/$fileName";
    }
}