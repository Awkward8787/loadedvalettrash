<?php

declare(strict_types=1);

namespace Lvt;

final class Pdf
{
    /** @param list<string> $lines */
    public static function document(string $title, array $lines): string
    {
        $lines = array_merge([$title, ''], $lines);
        $pages = array_chunk($lines, 46);
        if ($pages === []) {
            $pages = [['']];
        }
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageIds = [];
        $fontId = 3 + (count($pages) * 2);
        $next = 3;
        $pageObjects = [];
        $contentObjects = [];
        foreach ($pages as $pageLines) {
            $pageId = $next++;
            $contentId = $next++;
            $pageIds[] = $pageId;
            $content = self::stream($pageLines);
            $pageObjects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents ' . $contentId . ' 0 R /Resources << /Font << /F1 ' . $fontId . ' 0 R >> >> >>';
            $contentObjects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }
        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds));
        $objects[] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [' . $kids . '] >>';
        $max = $fontId;
        $ordered = array_fill(1, $max, '');
        $ordered[1] = $objects[0];
        $ordered[2] = $objects[1];
        foreach ($pageObjects as $id => $body) {
            $ordered[$id] = $body;
        }
        foreach ($contentObjects as $id => $body) {
            $ordered[$id] = $body;
        }
        $ordered[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        for ($i = 1; $i <= $max; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $ordered[$i] . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($max + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . ($max + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    /** @param list<string> $lines */
    private static function stream(array $lines): string
    {
        $out = "BT /F1 11 Tf 54 750 Td 14 TL\n";
        foreach ($lines as $line) {
            $out .= '(' . self::escape($line) . ") Tj T*\n";
        }
        $out .= "ET";
        return $out;
    }

    private static function escape(string $text): string
    {
        $text = str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", '', ' '], $text);
        $clean = '';
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $ord = ord($text[$i]);
            $clean .= ($ord >= 32 && $ord <= 126) ? $text[$i] : '?';
        }
        return $clean;
    }
}
