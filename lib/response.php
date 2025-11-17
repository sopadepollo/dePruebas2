<?php

declare(strict_types=1);

namespace App\Lib;

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse(string $message, int $status = 400, array $extra = []): void
{
    $payload = array_merge(['error' => $message], $extra);
    jsonResponse($payload, $status);
}
