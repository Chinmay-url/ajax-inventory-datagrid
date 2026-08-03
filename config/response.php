<?php
declare(strict_types=1);

final class JsonResponse
{
    public static function send(bool $success, int $status, string $message, array $data = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}
