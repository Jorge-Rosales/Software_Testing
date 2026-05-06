<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { echo json_encode(['error' => 'No autenticado']); exit; }

require_once __DIR__ . '/../includes/db.php';
$db   = getDB();
$uid  = $_SESSION['usuario_id'];
$body = json_decode(file_get_contents('php://input'), true);

$libro_id = (int)($body['libro_id'] ?? 0);
if ($libro_id <= 0) { echo json_encode(['error' => 'Datos inválidos']); exit; }

$stmt = $db->prepare("DELETE FROM carrito WHERE usuario_id = ? AND libro_id = ?");
$stmt->bind_param('ii', $uid, $libro_id);
$stmt->execute();
$stmt->close();

$cartTotal = (int)$db->query("SELECT SUM(cantidad) FROM carrito WHERE usuario_id = $uid")->fetch_row()[0];
echo json_encode(['success' => true, 'cart_total' => $cartTotal]);
