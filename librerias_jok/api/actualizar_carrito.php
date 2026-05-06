<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { echo json_encode(['error' => 'No autenticado']); exit; }

require_once __DIR__ . '/../includes/db.php';
$db   = getDB();
$uid  = $_SESSION['usuario_id'];
$body = json_decode(file_get_contents('php://input'), true);

$libro_id = (int)($body['libro_id'] ?? 0);
$cantidad = (int)($body['cantidad'] ?? 0);

if ($libro_id <= 0 || $cantidad <= 0) { echo json_encode(['error' => 'Datos inválidos']); exit; }

// Check stock
$stmt = $db->prepare("SELECT precio, stock FROM libros WHERE id = ?");
$stmt->bind_param('i', $libro_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) { echo json_encode(['error' => 'Libro no encontrado']); exit; }
if ($cantidad > $res['stock']) {
    echo json_encode(['error' => "Stock insuficiente ({$res['stock']} disponible)"]);
    exit;
}

$stmt = $db->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND libro_id = ?");
$stmt->bind_param('iii', $cantidad, $uid, $libro_id);
$stmt->execute();
$stmt->close();

$cartTotal    = (int)$db->query("SELECT SUM(cantidad) FROM carrito WHERE usuario_id = $uid")->fetch_row()[0];
$subtotalItem = $res['precio'] * $cantidad;

echo json_encode(['success' => true, 'cart_total' => $cartTotal, 'subtotal_item' => $subtotalItem]);
