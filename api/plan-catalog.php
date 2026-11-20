<?php

declare(strict_types=1);

const ECERT_PLAN_PRICES = [
    'normal' => [
        1 => 40,
        3 => 65,
        6 => 125,
        12 => 250,
    ],
    'government' => [
        1 => 30,
        3 => 50,
        6 => 100,
        12 => 200,
    ],
];

function ecert_plan_id(string $type, int $duration): string
{
    return sprintf('%s-%02dm', $type, $duration);
}

/**
 * @return array<string, array{id: string, type: string, duration: int, price: int}>
 */
function ecert_plan_catalog(): array
{
    $catalog = [];

    foreach (ECERT_PLAN_PRICES as $type => $durations) {
        foreach ($durations as $duration => $price) {
            $id = ecert_plan_id($type, $duration);
            $catalog[$id] = [
                'id' => $id,
                'type' => $type,
                'duration' => $duration,
                'price' => $price,
            ];
        }
    }

    return $catalog;
}

/**
 * @return array{id: string, type: string, duration: int, price: int}|null
 */
function ecert_find_plan(mixed $type, mixed $duration): ?array
{
    if (!is_string($type) || !is_string($duration)) {
        return null;
    }

    if (!array_key_exists($type, ECERT_PLAN_PRICES) || !preg_match('/^(1|3|6|12)$/D', $duration)) {
        return null;
    }

    $durationMonths = (int) $duration;
    $price = ECERT_PLAN_PRICES[$type][$durationMonths] ?? null;

    if ($price === null) {
        return null;
    }

    return [
        'id' => ecert_plan_id($type, $durationMonths),
        'type' => $type,
        'duration' => $durationMonths,
        'price' => $price,
    ];
}

/**
 * Resolve only the public plan selectors. Extra values, including a supplied price, are rejected.
 *
 * @param array<string, mixed> $input
 * @return array{id: string, type: string, duration: int, price: int}|null
 */
function ecert_resolve_plan(array $input): ?array
{
    $keys = array_keys($input);
    sort($keys);

    if ($keys !== ['duration', 'type']) {
        return null;
    }

    return ecert_find_plan($input['type'], $input['duration']);
}
