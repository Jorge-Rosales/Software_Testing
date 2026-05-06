<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { echo json_encode(['error' => 'No autenticado']); exit; }

require_once __DIR__ . '/../includes/db.php';
$db  = getDB();
$uid = $_SESSION['usuario_id'];
$body = json_decode(file_get_contents('php://input'), true);

// ── Recomprar ──
if (isset($body['recomprar_orden_id'])) {
    $oid = (int)$body['recomprar_orden_id'];
    // Verify ownership
    $chk = $db->prepare("SELECT id FROM ordenes WHERE id = ? AND usuario_id = ?");
    $chk->bind_param('ii', $oid, $uid);
    $chk->execute();
    $chk->store_result();
    if (!$chk->num_rows) { echo json_encode(['error' => 'Orden no encontrada']); exit; }
    $chk->close();

    $items = $db->query("SELECT libro_id, cantidad FROM orden_items WHERE orden_id = $oid")->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $it) {
        $stmt = $db->prepare("SELECT stock FROM libros WHERE id = ?");
        $stmt->bind_param('i', $it['libro_id']);
        $stmt->execute();
        $stmt->bind_result($stock);
        $stmt->fetch();
        $stmt->close();
        $qty = min($it['cantidad'], $stock);
        if ($qty < 1) continue;
        $ins = $db->prepare("INSERT INTO carrito (usuario_id, libro_id, cantidad) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
        $ins->bind_param('iiii', $uid, $it['libro_id'], $qty, $qty);
        $ins->execute();
        $ins->close();
    }
    $ct = getCartTotal($db, $uid);
    echo json_encode(['success' => true, 'cart_total' => $ct]);
    exit;
}

// ── Normal add ──
$libro_id = (int)($body['libro_id'] ?? 0);
$cantidad = (int)($body['cantidad'] ?? 1);
if ($libro_id <= 0 || $cantidad <= 0) { echo json_encode(['error' => 'Datos inválidos']); exit; }

// Check stock
$stmt = $db->prepare("SELECT stock FROM libros WHERE id = ?");
$stmt->bind_param('i', $libro_id);
$stmt->execute();
$stmt->bind_result($stock);
$stmt->fetch();
$stmt->close();

if ($stock === null)   { echo json_encode(['error' => 'Libro no encontrado']); exit; }
if ($cantidad > $stock){ echo json_encode(['error' => "Stock insuficiente ($stock disponible)"]); exit; }

// Check existing cart qty
$stmt = $db->prepare("SELECT cantidad FROM carrito WHERE usuario_id = ? AND libro_id = ?");
$stmt->bind_param('ii', $uid, $libro_id);
$stmt->execute();
$stmt->bind_result($existingQty);
$existingQty = 0;
$stmt->fetch();
$stmt->close();

$newQty = $existingQty + $cantidad;
if ($newQty > $stock) $newQty = $stock;

$ins = $db->prepare("INSERT INTO carrito (usuario_id, libro_id, cantidad) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cantidad = ?");
$ins->bind_param('iiii', $uid, $libro_id, $newQty, $newQty);
$ins->execute();
$ins->close();

$ct = getCartTotal($db, $uid);
echo json_encode(['success' => true, 'cart_total' => $ct]);

function getCartTotal($db, $uid) {
    $r = $db->query("SELECT SUM(cantidad) FROM carrito WHERE usuario_id = $uid");
    return (int)($r->fetch_row()[0] ?? 0);
}
