<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'No autenticado']); exit; }
require_once __DIR__ . '/../includes/db.php';
$db  = getDB();
$uid = (int)($_GET['uid'] ?? 0);
if (!$uid) { echo json_encode(['error' => 'ID inválido']); exit; }

$ordenes = $db->query("
    SELECT id, DATE_FORMAT(fecha,'%d/%m/%Y %H:%i') AS fecha, total, estado
    FROM ordenes WHERE usuario_id = $uid ORDER BY fecha DESC LIMIT 20
")->fetch_all(MYSQLI_ASSOC);
echo json_encode(['ordenes' => $ordenes]);
