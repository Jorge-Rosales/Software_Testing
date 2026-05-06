<?php
require_once __DIR__ . '/../includes/check_admin.php';
require_once __DIR__ . '/../includes/db.php';
$adminTitle = 'Gestión de Libros';
$db = getDB();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)$_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM libros WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();
    header('Location: /librerias_jok/admin/libros.php?msg=deleted');
    exit;
}

$msg = $_GET['msg'] ?? '';

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$search  = trim($_GET['q'] ?? '');

$where  = '1=1';
$params = [];
$types  = '';
if ($search !== '') {
    $where    = "(l.titulo LIKE ? OR l.autor LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types    = 'ss';
}

$total = $db->query("SELECT COUNT(*) FROM libros l WHERE $where" . ($types ? "" : ""))->fetch_row()[0];
// If search, prepare count differently
if ($types) {
    $cStmt = $db->prepare("SELECT COUNT(*) FROM libros l WHERE $where");
    $cStmt->bind_param($types, ...$params);
    $cStmt->execute();
    $cStmt->bind_result($total);
    $cStmt->fetch();
    $cStmt->close();
}

$totalPages = max(1, ceil($total / $perPage));
$page   = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT l.*, c.nombre AS cat FROM libros l
        LEFT JOIN categorias c ON c.id = l.categoria_id
        WHERE $where
        ORDER BY l.id DESC LIMIT ? OFFSET ?";
$p2 = array_merge($params, [$perPage, $offset]);
$t2 = $types . 'ii';
$stmt = $db->prepare($sql);
$stmt->bind_param($t2, ...$p2);
$stmt->execute();
$libros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/header_admin.php';
?>

<?php if ($msg === 'deleted'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="bi bi-check-circle me-2"></i>Libro eliminado correctamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php elseif ($msg === 'saved'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="bi bi-check-circle me-2"></i>Libro guardado correctamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control form-control-sm" style="width:250px;"
           value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por título o autor...">
    <button class="btn btn-sm btn-gold" type="submit"><i class="bi bi-search"></i></button>
    <?php if ($search): ?><a href="/librerias_jok/admin/libros.php" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
  </form>
  <a href="/librerias_jok/admin/crear_libro.php" class="btn btn-gold">
    <i class="bi bi-plus-lg me-2"></i>Nuevo libro
  </a>
</div>

<div class="bg-white rounded-3 shadow-sm overflow-hidden">
  <table class="table admin-table mb-0">
    <thead>
      <tr>
        <th>ID</th><th>Portada</th><th>Título / Autor</th><th>Categoría</th>
        <th>Precio</th><th>Stock</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($libros as $l): ?>
      <tr>
        <td class="text-muted small"><?= $l['id'] ?></td>
        <td>
          <?php if ($l['imagen']): ?>
            <img src="/librerias_jok/uploads/<?= htmlspecialchars($l['imagen']) ?>"
                 style="width:32px;height:44px;object-fit:cover;border-radius:4px;" alt="">
          <?php else: ?>
            <div style="width:32px;height:44px;background:#1a1a1a;border-radius:4px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-book" style="color:var(--gold);font-size:0.9rem;"></i>
            </div>
          <?php endif; ?>
        </td>
        <td>
          <div class="fw-bold" style="font-size:0.88rem;"><?= htmlspecialchars($l['titulo']) ?></div>
          <div class="text-muted" style="font-size:0.78rem;font-style:italic;"><?= htmlspecialchars($l['autor']) ?></div>
        </td>
        <td><span class="badge-gold"><?= htmlspecialchars($l['cat'] ?? '—') ?></span></td>
        <td class="fw-bold">$<?= number_format($l['precio'], 2) ?></td>
        <td>
          <?php if ($l['stock'] <= 0): ?>
            <span class="badge-red">0</span>
          <?php elseif ($l['stock'] <= 5): ?>
            <span class="badge-gold"><?= $l['stock'] ?></span>
          <?php else: ?>
            <span class="badge-green"><?= $l['stock'] ?></span>
          <?php endif; ?>
        </td>
        <td>
          <div class="d-flex gap-2">
            <a href="/librerias_jok/admin/crear_libro.php?edit=<?= $l['id'] ?>"
               class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <form method="POST" onsubmit="return confirm('¿Eliminar este libro?');" class="d-inline">
              <input type="hidden" name="delete_id" value="<?= $l['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($libros)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/footer_admin.php'; ?>
