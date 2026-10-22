<?php

declare(strict_types=1);

namespace Lvt;

final class Csv
{
    /** @return list<array<string,string>> */
    public static function parseFile(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new HttpException(422, 'Could not read that CSV file.');
        }
        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            throw new HttpException(422, 'The CSV file is empty.');
        }
        $keys = [];
        foreach ($header as $col) {
            $key = strtolower(trim((string) $col));
            $key = (string) preg_replace('/^\xEF\xBB\xBF/', '', $key);
            $keys[] = $key;
        }
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }
            $row = [];
            $blank = true;
            foreach ($keys as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $value = trim((string) ($data[$i] ?? ''));
                if ($value !== '') {
                    $blank = false;
                }
                $row[$key] = $value;
            }
            if (!$blank) {
                $rows[] = $row;
            }
            if (count($rows) > 1000) {
                fclose($handle);
                throw new HttpException(422, 'CSV imports are limited to 1,000 rows.');
            }
        }
        fclose($handle);
        return $rows;
    }

    /** @param list<string> $header @param list<list<string>> $rows */
    public static function download(string $filename, array $header, array $rows): Response
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            throw new \RuntimeException('Could not build CSV.');
        }
        fputcsv($fh, $header);
        foreach ($rows as $row) {
            fputcsv($fh, array_map(static fn ($v): string => csv_safe((string) $v), $row));
        }
        rewind($fh);
        $body = (string) stream_get_contents($fh);
        fclose($fh);
        return Response::text($body, 200, 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
