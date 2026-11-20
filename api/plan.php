<?php

declare(strict_types=1);

require_once __DIR__ . '/plan-catalog.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$plan = ecert_resolve_plan($_GET);

if ($plan === null) {
    respond(422, ['success' => false, 'message' => 'Select a valid plan.']);
}

respond(200, [
    'success' => true,
    'plan' => $plan,
]);
