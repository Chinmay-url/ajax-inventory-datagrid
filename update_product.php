<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::send(false, 405, 'Method not allowed.');
}

Csrf::validateRequest();

$payload = json_decode(file_get_contents('php://input'), true);

$id    = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);
$field = htmlspecialchars(trim((string)($payload['field'] ?? '')), ENT_QUOTES, 'UTF-8');
$value = $payload['value'] ?? null;

// Whitelist of columns that are allowed to be edited inline. Never build
// column names from raw client input.
$allowedFields = ['price', 'stock', 'status', 'name'];

if ($id === false || $id === null || !in_array($field, $allowedFields, true)) {
    JsonResponse::send(false, 422, 'Invalid product id or field.');
}

// Field-specific validation
switch ($field) {
    case 'price':
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < 0) {
            JsonResponse::send(false, 422, 'Price must be a non-negative number.');
        }
        break;

    case 'stock':
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value < 0) {
            JsonResponse::send(false, 422, 'Stock must be a non-negative integer.');
        }
        break;

    case 'status':
        $allowedStatuses = ['IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK'];
        if (!in_array($value, $allowedStatuses, true)) {
            JsonResponse::send(false, 422, 'Invalid status value.');
        }
        break;

    case 'name':
        $value = htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
        if ($value === '' || mb_strlen($value) > 200) {
            JsonResponse::send(false, 422, 'Name must be between 1 and 200 characters.');
        }
        break;
}

$pdo = Database::getConnection();

try {
    // $field is validated against a strict whitelist above, so it is safe
    // to interpolate directly into the column position of the query.
    $stmt = $pdo->prepare("UPDATE products SET {$field} = :value WHERE id = :id AND is_deleted = 0");
    $stmt->execute(['value' => $value, 'id' => $id]);

    if ($stmt->rowCount() === 0) {
        JsonResponse::send(false, 404, 'Product not found or already deleted.');
    }

    JsonResponse::send(true, 200, 'Product updated successfully.', [
        'id'    => $id,
        'field' => $field,
        'value' => $value,
    ]);
} catch (PDOException $e) {
    error_log('Update product error: ' . $e->getMessage());
    JsonResponse::send(false, 500, 'Internal server error while updating product.');
}
