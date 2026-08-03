<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::send(false, 405, 'Method not allowed.');
}

Csrf::validateRequest();

$payload = json_decode(file_get_contents('php://input'), true);
$ids     = $payload['ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
    JsonResponse::send(false, 422, 'An array of product ids is required.');
}

$cleanIds = [];
foreach ($ids as $rawId) {
    $id = filter_var($rawId, FILTER_VALIDATE_INT);
    if ($id !== false) {
        $cleanIds[] = $id;
    }
}

if (empty($cleanIds)) {
    JsonResponse::send(false, 422, 'No valid product ids provided.');
}

$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $stmt = $pdo->prepare("UPDATE products SET is_deleted = 1 WHERE id IN ({$placeholders})");
    $stmt->execute($cleanIds);

    $affected = $stmt->rowCount();
    $pdo->commit();

    JsonResponse::send(true, 200, "Soft-deleted {$affected} product(s) successfully.", [
        'deleted_count' => $affected,
        'ids'           => $cleanIds,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Batch delete error: ' . $e->getMessage());
    JsonResponse::send(false, 500, 'Internal server error during batch delete.');
}
