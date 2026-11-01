<?php

declare(strict_types=1);

namespace Lvt\Junk;

/**
 * Junk pickup sizes and estimate math.
 * A missing rate is null. This class never invents a dollar amount.
 */
final class Quote
{
    public const FOLLOW_UP = 'The team will follow up with an estimate.';

    public const ESTIMATE_LABEL = 'Estimate only, final price confirmed on site.';

    /** @return array<string,string> */
    public static function items(): array
    {
        return [
            'couch' => 'Couch',
            'mattress' => 'Mattress',
            'appliance' => 'Appliance',
            'table' => 'Table',
            'bags' => 'Bags or boxes',
        ];
    }

    /** @return array<string,string> */
    public static function loads(): array
    {
        return [
            'quarter' => '1/4 truck',
            'half' => '1/2 truck',
            'full' => 'Full truck',
        ];
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_merge(array_keys(self::items()), array_keys(self::loads()));
    }

    /**
     * @param array<string,mixed>|null $row
     * @return array{low:int,high:int}|null
     */
    public static function pair(?array $row, string $code): ?array
    {
        if ($row === null || !in_array($code, self::codes(), true)) {
            return null;
        }
        $low = self::cents($row[$code . '_low_cents'] ?? null);
        $high = self::cents($row[$code . '_high_cents'] ?? null);
        if ($low === null && $high === null) {
            return null;
        }
        if ($low === null) {
            $low = $high;
        }
        if ($high === null) {
            $high = $low;
        }
        if ($low === null || $high === null) {
            return null;
        }
        if ($high < $low) {
            return ['low' => $high, 'high' => $low];
        }
        return ['low' => $low, 'high' => $high];
    }

    /**
     * @param array<string,mixed>|null $row
     * @return array{items:array<string,array{low:int,high:int}|null>,loads:array<string,array{low:int,high:int}|null>}
     */
    public static function book(?array $row): array
    {
        $items = [];
        foreach (self::items() as $code => $label) {
            $items[$code] = self::pair($row, $code);
        }
        $loads = [];
        foreach (self::loads() as $code => $label) {
            $loads[$code] = self::pair($row, $code);
        }
        return ['items' => $items, 'loads' => $loads];
    }

    /**
     * @param array<string,int> $quantities
     * @param array{items:array<string,array{low:int,high:int}|null>,loads:array<string,array{low:int,high:int}|null>} $book
     * @return array{low:int,high:int}|null Null when nothing was chosen or any chosen line has no rate.
     */
    public static function total(string $mode, array $quantities, ?string $load, array $book): ?array
    {
        if ($mode === 'load') {
            if ($load === null || !array_key_exists($load, self::loads())) {
                return null;
            }
            $pair = $book['loads'][$load] ?? null;
            return is_array($pair) ? $pair : null;
        }
        $low = 0;
        $high = 0;
        $any = false;
        foreach (self::items() as $code => $label) {
            $qty = (int) ($quantities[$code] ?? 0);
            if ($qty < 1) {
                continue;
            }
            $any = true;
            $pair = $book['items'][$code] ?? null;
            if (!is_array($pair)) {
                return null;
            }
            $low += $pair['low'] * $qty;
            $high += $pair['high'] * $qty;
        }
        if (!$any) {
            return null;
        }
        return ['low' => $low, 'high' => $high];
    }

    /** @param array{low:int,high:int}|null $pair */
    public static function moneyRange(?array $pair): string
    {
        if ($pair === null) {
            return '';
        }
        if ($pair['low'] === $pair['high']) {
            return money($pair['low']);
        }
        return money($pair['low']) . ' to ' . money($pair['high']);
    }

    public static function selectionLabel(string $mode, ?string $itemsJson, ?string $load): string
    {
        if ($mode === 'load') {
            return self::loads()[$load ?? ''] ?? 'Load';
        }
        $decoded = json_decode((string) $itemsJson, true);
        if (!is_array($decoded)) {
            return 'Items';
        }
        $parts = [];
        foreach ($decoded as $line) {
            if (!is_array($line)) {
                continue;
            }
            $code = (string) ($line['code'] ?? '');
            $qty = (int) ($line['qty'] ?? 0);
            $label = self::items()[$code] ?? null;
            if ($label === null || $qty < 1) {
                continue;
            }
            $parts[] = $label . ' x ' . $qty;
        }
        return $parts === [] ? 'Items' : implode(', ', $parts);
    }

    private static function cents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
