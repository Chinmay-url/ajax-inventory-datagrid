<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::send(false, 405, 'Method not allowed.');
}

$search = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '';
$search = htmlspecialchars(trim((string) $search), ENT_QUOTES, 'UTF-8');

$page  = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 10, 'max_range' => 200]]);
$offset = ($page - 1) * $limit;

$pdo = Database::getConnection();

try {
    $where  = 'WHERE is_deleted = 0';
    $params = [];

    if ($search !== '') {
        $where .= ' AND (sku LIKE :search OR name LIKE :search2)';
        $params['search']  = $search . '%';   // leading-wildcard-free = index-friendly
        $params['search2'] = $search . '%';
    }

    // Total count for pagination controls
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products {$where}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['total'];

    $dataStmt = $pdo->prepare(
        "SELECT id, sku, name, price, stock, status
         FROM products
         {$where}
         ORDER BY id ASC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $dataStmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();

    $products = $dataStmt->fetchAll();

    JsonResponse::send(true, 200, 'Products fetched successfully.', [
        'products'   => $products,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $limit),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Fetch products error: ' . $e->getMessage());
    JsonResponse::send(false, 500, 'Internal server error while fetching products.');
}
