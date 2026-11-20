<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/plan-catalog.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    } else {
        fwrite(STDOUT, "PASS: {$message}\n");
    }
};

$expected = [
    'normal-01m' => ['id' => 'normal-01m', 'type' => 'normal', 'duration' => 1, 'price' => 40],
    'normal-03m' => ['id' => 'normal-03m', 'type' => 'normal', 'duration' => 3, 'price' => 65],
    'normal-06m' => ['id' => 'normal-06m', 'type' => 'normal', 'duration' => 6, 'price' => 125],
    'normal-12m' => ['id' => 'normal-12m', 'type' => 'normal', 'duration' => 12, 'price' => 250],
    'government-01m' => ['id' => 'government-01m', 'type' => 'government', 'duration' => 1, 'price' => 30],
    'government-03m' => ['id' => 'government-03m', 'type' => 'government', 'duration' => 3, 'price' => 50],
    'government-06m' => ['id' => 'government-06m', 'type' => 'government', 'duration' => 6, 'price' => 100],
    'government-12m' => ['id' => 'government-12m', 'type' => 'government', 'duration' => 12, 'price' => 200],
];

$catalog = ecert_plan_catalog();
$assert($catalog === $expected, 'catalog contains the eight exact stable records');

foreach ($expected as $plan) {
    $resolved = ecert_resolve_plan([
        'type' => $plan['type'],
        'duration' => (string) $plan['duration'],
    ]);
    $assert($resolved === $plan, "resolver derives {$plan['id']} and its approved price");
}

$invalidInputs = [
    ['type' => 'normal', 'duration' => '12', 'price' => '1'],
    ['type' => 'normal', 'duration' => '2'],
    ['type' => 'government', 'duration' => '01'],
    ['type' => 'Government', 'duration' => '1'],
    ['type' => 'normal', 'duration' => 1],
    ['type' => ['normal'], 'duration' => '1'],
    ['duration' => '1'],
    [],
];

foreach ($invalidInputs as $index => $input) {
    $assert(ecert_resolve_plan($input) === null, 'invalid or tampered input is rejected #' . ($index + 1));
}

if ($failures !== []) {
    exit(1);
}

fwrite(STDOUT, "All plan catalog assertions passed.\n");
