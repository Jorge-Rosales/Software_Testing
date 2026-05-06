<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$q     = trim($_GET['q']    ?? '');
$cat   = (int)($_GET['cat'] ?? 0);
$min   = (float)($_GET['min'] ?? 0);
$max   = (float)($_GET['max'] ?? 0);
$orden = $_GET['orden'] ?? 'nombre';
$page  = max(1, (int)($_GET['page'] ?? 1));
$per   = min(8, max(1, (int)($_GET['per'] ?? 8)));
$limit = (int)($_GET['limit'] ?? $per);
$offset = ($page - 1) * $per;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($q !== '') {
    $where[]  = "(titulo LIKE ? OR autor LIKE ?)";
    $like     = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($cat > 0) {
    $where[]  = "categoria_id = ?";
    $params[] = $cat;
    $types   .= 'i';
}
if ($min > 0) { $where[] = "precio >= ?"; $params[] = $min; $types .= 'd'; }
if ($max > 0) { $where[] = "precio <= ?"; $params[] = $max; $types .= 'd'; }

$whereSQL = implode(' AND ', $where);

$allowedOrden = ['precio_asc'=>'precio ASC','precio_desc'=>'precio DESC','nombre'=>'titulo ASC','popular'=>'titulo ASC'];
$orderSQL = $allowedOrden[$orden] ?? 'titulo ASC';

$db = getDB();
$sql = "SELECT l.*, c.nombre AS categoria_nombre FROM libros l LEFT JOIN categorias c ON c.id = l.categoria_id WHERE $whereSQL ORDER BY $orderSQL LIMIT ? OFFSET ?";

$params2 = array_merge($params, [$limit, $offset]);
$types2  = $types . 'ii';
$stmt = $db->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$libros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM libros l WHERE $whereSQL");
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();

echo json_encode(['success' => true, 'libros' => $libros, 'total' => $total]);
