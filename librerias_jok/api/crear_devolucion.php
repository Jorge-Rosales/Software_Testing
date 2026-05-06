<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { echo json_encode(['error' => 'No autenticado']); exit; }

require_once __DIR__ . '/../includes/db.php';
$db   = getDB();
$uid  = $_SESSION['usuario_id'];
$body = json_decode(file_get_contents('php://input'), true);

$libro_id = (int)($body['libro_id'] ?? 0);
$orden_id = (int)($body['orden_id'] ?? 0);
$cantidad = (int)($body['cantidad'] ?? 0);
$motivo   = trim($body['motivo'] ?? '');

if ($libro_id <= 0 || $orden_id <= 0 || $cantidad <= 0) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// Verify order belongs to user
$stmt = $db->prepare("SELECT id FROM ordenes WHERE id = ? AND usuario_id = ? AND estado = 'completada'");
$stmt->bind_param('ii', $orden_id, $uid);
$stmt->execute();
$stmt->store_result();
if (!$stmt->num_rows) { echo json_encode(['error' => 'Orden no válida']); exit; }
$stmt->close();

// Verify quantity <= purchased
$stmt = $db->prepare("SELECT cantidad FROM orden_items WHERE orden_id = ? AND libro_id = ?");
$stmt->bind_param('ii', $orden_id, $libro_id);
$stmt->execute();
$stmt->bind_result($cantComprada);
$stmt->fetch();
$stmt->close();

if (!$cantComprada) { echo json_encode(['error' => 'Libro no encontrado en la orden']); exit; }
if ($cantidad > $cantComprada) {
    echo json_encode(['error' => "Cantidad máxima a devolver: $cantComprada"]);
    exit;
}

// Check no existing active return for same item
$stmt = $db->prepare("SELECT id FROM devoluciones WHERE usuario_id = ? AND orden_id = ? AND libro_id = ? AND estado != 'rechazada'");
$stmt->bind_param('iii', $uid, $orden_id, $libro_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows) { echo json_encode(['error' => 'Ya tienes una devolución activa para este libro']); exit; }
$stmt->close();

// Insert
$stmt = $db->prepare("INSERT INTO devoluciones (usuario_id, libro_id, orden_id, cantidad, motivo, estado) VALUES (?,?,?,?,?,'pendiente')");
$stmt->bind_param('iiiis', $uid, $libro_id, $orden_id, $cantidad, $motivo);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Error al registrar la devolución']);
}
$stmt->close();
