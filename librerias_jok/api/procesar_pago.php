<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { echo json_encode(['error' => 'No autenticado']); exit; }

require_once __DIR__ . '/../includes/db.php';
$db   = getDB();
$uid  = $_SESSION['usuario_id'];
$body = json_decode(file_get_contents('php://input'), true);
$direccion = trim($body['direccion'] ?? '');

if (empty($direccion)) { echo json_encode(['error' => 'Dirección requerida']); exit; }

// Get cart
$stmt = $db->prepare("
    SELECT c.cantidad, c.libro_id, l.precio, l.stock, l.titulo
    FROM carrito c
    JOIN libros l ON l.id = c.libro_id
    WHERE c.usuario_id = ?
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) { echo json_encode(['error' => 'El carrito está vacío']); exit; }

// Validate stock
foreach ($items as $it) {
    if ($it['cantidad'] > $it['stock']) {
        echo json_encode(['error' => "Stock insuficiente para: {$it['titulo']}"]);
        exit;
    }
}

// Calculate totals
$subtotal = 0;
foreach ($items as $it) $subtotal += $it['precio'] * $it['cantidad'];
$iva   = round($subtotal * 0.16, 2);
$total = round($subtotal + $iva, 2);
$subtotal = round($subtotal, 2);

// Begin transaction
$db->begin_transaction();
try {
    // Insert order
    $stmt = $db->prepare("INSERT INTO ordenes (usuario_id, subtotal, iva, total, estado, direccion) VALUES (?,?,?,?,'completada',?)");
    $stmt->bind_param('iddds', $uid, $subtotal, $iva, $total, $direccion);
    $stmt->execute();
    $orden_id = $db->insert_id;
    $stmt->close();

    // Insert items & update stock
    foreach ($items as $it) {
        $ins = $db->prepare("INSERT INTO orden_items (orden_id, libro_id, cantidad, precio_unitario) VALUES (?,?,?,?)");
        $ins->bind_param('iiid', $orden_id, $it['libro_id'], $it['cantidad'], $it['precio']);
        $ins->execute();
        $ins->close();

        $upd = $db->prepare("UPDATE libros SET stock = stock - ? WHERE id = ?");
        $upd->bind_param('ii', $it['cantidad'], $it['libro_id']);
        $upd->execute();
        $upd->close();
    }

    // Clear cart
    $del = $db->prepare("DELETE FROM carrito WHERE usuario_id = ?");
    $del->bind_param('i', $uid);
    $del->execute();
    $del->close();

    $db->commit();
    echo json_encode(['success' => true, 'orden_id' => $orden_id]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['error' => 'Error al procesar el pago: ' . $e->getMessage()]);
}
