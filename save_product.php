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

if (!is_array($payload)) {
    JsonResponse::send(false, 400, 'Invalid JSON payload.');
}

// `id` is optional: omit / null for a new product, provide it to update.
$id = ($payload['id'] ?? null) === null || ($payload['id'] ?? '') === ''
    ? null
    : filter_var($payload['id'], FILTER_VALIDATE_INT);

$sku    = htmlspecialchars(trim((string)($payload['sku'] ?? '')), ENT_QUOTES, 'UTF-8');
$name   = htmlspecialchars(trim((string)($payload['name'] ?? '')), ENT_QUOTES, 'UTF-8');
$price  = filter_var($payload['price'] ?? null, FILTER_VALIDATE_FLOAT);
$stock  = filter_var($payload['stock'] ?? null, FILTER_VALIDATE_INT);
$status = $payload['status'] ?? '';

$allowedStatuses = ['IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK'];

if ($sku === '' || mb_strlen($sku) > 50) {
    JsonResponse::send(false, 422, 'SKU must be between 1 and 50 characters.');
}
if ($name === '' || mb_strlen($name) > 200) {
    JsonResponse::send(false, 422, 'Name must be between 1 and 200 characters.');
}
if ($price === false || $price < 0) {
    JsonResponse::send(false, 422, 'Price must be a non-negative number.');
}
if ($stock === false || $stock < 0) {
    JsonResponse::send(false, 422, 'Stock must be a non-negative integer.');
}
if (!in_array($status, $allowedStatuses, true)) {
    JsonResponse::send(false, 422, 'Invalid status value.');
}

$pdo = Database::getConnection();

try {
    // SKU must be unique among non-deleted products (case-insensitive under utf8mb4_unicode_ci).
    $check = $pdo->prepare('SELECT id FROM products WHERE sku = :sku AND is_deleted = 0 AND id <> :id');
    $check->execute(['sku' => $sku, 'id' => $id ?? 0]);

    if ($check->fetch()) {
        JsonResponse::send(false, 409, 'A product with this SKU already exists.');
    }

    if ($id === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO products (sku, name, price, stock, status)
             VALUES (:sku, :name, :price, :stock, :status)'
        );
        $stmt->execute([
            'sku'    => $sku,
            'name'   => $name,
            'price'  => $price,
            'stock'  => $stock,
            'status' => $status,
        ]);

        $id = (int) $pdo->lastInsertId();
        $message = 'Product added successfully.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE products
             SET sku = :sku, name = :name, price = :price, stock = :stock, status = :status
             WHERE id = :id AND is_deleted = 0'
        );
        $stmt->execute([
            'sku'    => $sku,
            'name'   => $name,
            'price'  => $price,
            'stock'  => $stock,
            'status' => $status,
            'id'     => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            JsonResponse::send(false, 404, 'Product not found or already deleted.');
        }
        $message = 'Product updated successfully.';
    }

    JsonResponse::send(true, 200, $message, [
        'id'    => $id,
        'sku'   => $sku,
        'name'  => $name,
        'price' => $price,
        'stock' => $stock,
        'status' => $status,
    ]);
} catch (PDOException $e) {
    error_log('Save product error: ' . $e->getMessage());
    JsonResponse::send(false, 500, 'Internal server error while saving product.');
}
