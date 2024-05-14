<?php

namespace App\Services;

use Generator;

class CSVReader
{
    /**
     * @param string $filename
     * @return Generator
     * @throws \Exception
     */
    public static function readRows(string $file): Generator
    {
        if (!file_exists($filename)) {
            throw new \Exception('File not found.');
        }

        $file = fopen($filename, 'r');

        // Skip header row
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            yield [
                'date' => $row[0],
                'start_time' => $row[1],
                'end_time' => $row[2],
                'type' => $row[3],
            ];
        }

        fclose($file);
    }

    /**
     * @param $generator
     * @param $chunkSize
     * @return Generator
     */
    public static function chunkGenerator($generator, $chunkSize): Generator
    {
        $chunk = [];

        foreach ($generator as $row) {
            $chunk[] = $row;

            if (count($chunk) === $chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            yield $chunk;
        }
    }
}
