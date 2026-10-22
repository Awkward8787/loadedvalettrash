<?php

declare(strict_types=1);

namespace Lvt\Billing;

final class Calculator
{
    /** @return array{gross_cents:int,share_cents:int,due_cents:int} */
    public static function optIn(int $doors, int $rateCents, float $sharePercent): array
    {
        if ($doors < 0 || $rateCents < 0 || $sharePercent < 0 || $sharePercent > 100) {
            throw new \InvalidArgumentException('Invalid opt-in invoice inputs.');
        }
        $gross = $doors * $rateCents;
        $share = (int) round($gross * ($sharePercent / 100));
        return [
            'gross_cents' => $gross,
            'share_cents' => $share,
            'due_cents' => $gross - $share,
        ];
    }

    /** @return array{gross_cents:int,share_cents:int,due_cents:int} */
    public static function perDoor(int $doors, int $rateCents): array
    {
        if ($doors < 0 || $rateCents < 0) {
            throw new \InvalidArgumentException('Invalid per-door invoice inputs.');
        }
        $gross = $doors * $rateCents;
        return ['gross_cents' => $gross, 'share_cents' => 0, 'due_cents' => $gross];
    }

    /** @return array{gross_cents:int,share_cents:int,due_cents:int} */
    public static function flat(int $flatCents): array
    {
        if ($flatCents < 0) {
            throw new \InvalidArgumentException('Invalid flat invoice.');
        }
        return ['gross_cents' => $flatCents, 'share_cents' => 0, 'due_cents' => $flatCents];
    }

    public static function laborCents(float $hours, int $wageCents, float $payrollMultiplier): int
    {
        if ($hours < 0 || $wageCents < 0 || $payrollMultiplier < 1) {
            throw new \InvalidArgumentException('Invalid labor inputs.');
        }
        return (int) round($hours * $wageCents * $payrollMultiplier);
    }

    public static function profitCents(
        int $dueCents,
        int $laborCents,
        int $suppliesCents,
        int $gasCents,
        int $reserveCents,
        int $fixedShareCents
    ): int {
        return $dueCents - $laborCents - $suppliesCents - $gasCents - $reserveCents - $fixedShareCents;
    }

    /** @param array<int,float> $weights property id => minutes */
    public static function splitHours(float $hours, array $weights): array
    {
        if ($weights === []) {
            return [];
        }
        $total = 0.0;
        foreach ($weights as $weight) {
            $total += max(0, (float) $weight);
        }
        $ids = array_keys($weights);
        $out = [];
        $assigned = 0.0;
        $last = count($ids) - 1;
        foreach ($ids as $i => $id) {
            if ($i === $last) {
                $share = round($hours - $assigned, 4);
            } elseif ($total <= 0) {
                $share = round($hours / count($ids), 4);
                $assigned += $share;
            } else {
                $share = round($hours * (max(0, (float) $weights[$id]) / $total), 4);
                $assigned += $share;
            }
            $out[(int) $id] = $share;
        }
        return $out;
    }
}
