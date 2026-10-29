<?php

declare(strict_types=1);

namespace App\Support;

use DateInterval;
use DateTimeInterface;
use Generator;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/** Reads the first sheet of a CSV or Excel file, row by row, so large files stay light on memory. */
class Spreadsheet
{
    /** The file types an import accepts. */
    public const EXTENSIONS = ['csv', 'txt', 'xlsx'];

    /**
     * Each non-empty row as a list of strings (dates as YYYY-MM-DD, whole numbers without
     * decimals), keyed by its line in the sheet, so problems can point at the right row.
     *
     * @return Generator<int, array<int, string>>
     */
    public static function rows(string $path, string $extension): Generator
    {
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $line = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $line++;
                    $values = array_map(self::text(...), $row->toArray());

                    if (array_filter($values, fn (string $value) => $value !== '') !== []) {
                        yield $line => $values;
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }
    }

    private static function text(mixed $value): string
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof DateInterval => '',
            is_float($value) && floor($value) === $value => sprintf('%.0f', $value),
            is_bool($value) => $value ? 'yes' : 'no',
            default => trim((string) $value),
        };
    }
}
