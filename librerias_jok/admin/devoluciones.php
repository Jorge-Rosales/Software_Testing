<?php
require_once __DIR__ . '/../includes/check_admin.php';
require_once __DIR__ . '/../includes/db.php';
$adminTitle = 'Devoluciones';
$db  = getDB();
$msg = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dev_id = (int)($_POST['dev_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($dev_id && in_array($action, ['aprobar','rechazar'])) {
        $stmt = $db->prepare("SELECT libro_id, cantidad, estado FROM devoluciones WHERE id = ?");
        $stmt->bind_param('i', $dev_id);
        $stmt->execute();
        $dev = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($dev && $dev['estado'] === 'pendiente') {
            if ($action === 'aprobar') {
                // Restore stock
                $upd = $db->prepare("UPDATE libros SET stock = stock + ? WHERE id = ?");
                $upd->bind_param('ii', $dev['cantidad'], $dev['libro_id']);
                $upd->execute();
                $upd->close();
                $db->query("UPDATE devoluciones SET estado='aprobada' WHERE id=$dev_id");
                $msg = 'Devolución aprobada y stock restaurado.';
            } else {
                $db->query("UPDATE devoluciones SET estado='rechazada' WHERE id=$dev_id");
                $msg = 'Devolución rechazada.';
            }
        }
    }
}

$page    = max(1,(int)($_GET['page']??1));
$perPage = 15;
$filtro  = $_GET['estado'] ?? '';

$where = $filtro ? "WHERE d.estado = '$filtro'" : '';
$total = $db->query("SELECT COUNT(*) FROM devoluciones d $where")->fetch_row()[0];
$totalPages = max(1, ceil($total / $perPage));
$page  = min($page, $totalPages);
$offset = ($page-1)*$perPage;

$devs = $db->query("
    SELECT d.*, u.nombre AS cliente, u.email, l.titulo AS libro_titulo
    FROM devoluciones d
    JOIN usuarios u ON u.id = d.usuario_id
    JOIN libros l ON l.id = d.libro_id
    $where
    ORDER BY d.fecha DESC
    LIMIT $perPage OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/header_admin.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 mb-4">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="d-flex gap-2 mb-4">
  <?php $tabs = ['' => 'Todas', 'pendiente' => 'Pendientes', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas']; ?>
  <?php foreach ($tabs as $val => $label): ?>
  <a href="?estado=<?= $val ?>" class="btn btn-sm <?= $filtro===$val ? 'btn-gold':'btn-outline-secondary' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-3 shadow-sm overflow-hidden">
  <table class="table admin-table mb-0">
    <thead>
      <tr>
        <th>ID</th><th>Cliente</th><th>Libro</th><th>Cant.</th>
        <th>Motivo</th><th>Fecha</th><th>Estado</th><th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($devs as $d): ?>
      <tr>
        <td class="text-muted small"><?= $d['id'] ?></td>
        <td>
          <div style="font-size:0.88rem;font-weight:700;"><?= htmlspecialchars($d['cliente']) ?></div>
          <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($d['email']) ?></div>
        </td>
        <td style="font-size:0.88rem;"><?= htmlspecialchars($d['libro_titulo']) ?></td>
        <td class="fw-bold"><?= $d['cantidad'] ?></td>
        <td class="text-muted small" style="max-width:160px;">
          <?= $d['motivo'] ? htmlspecialchars(substr($d['motivo'],0,80)) . (strlen($d['motivo'])>80?'…':'') : '—' ?>
        </td>
        <td class="text-muted small"><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
        <td>
          <?php if ($d['estado']==='pendiente'): ?>
            <span class="badge-gold">Pendiente</span>
          <?php elseif ($d['estado']==='aprobada'): ?>
            <span class="badge-green">Aprobada</span>
          <?php else: ?>
            <span class="badge-red">Rechazada</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($d['estado']==='pendiente'): ?>
          <form method="POST" class="d-flex gap-1">
            <input type="hidden" name="dev_id" value="<?= $d['id'] ?>">
            <button name="action" value="aprobar" class="btn btn-sm btn-gold"
              onclick="return confirm('¿Aprobar esta devolución y restaurar stock?')">
              <i class="bi bi-check-lg"></i>
            </button>
            <button name="action" value="rechazar" class="btn btn-sm btn-outline-danger"
              onclick="return confirm('¿Rechazar esta devolución?')">
              <i class="bi bi-x-lg"></i>
            </button>
          </form>
          <?php else: ?>
            <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($devs)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Sin devoluciones.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-4">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($p=1;$p<=$totalPages;$p++): ?>
    <li class="page-item <?= $p===$page?'active':'' ?>">
      <a class="page-link" href="?page=<?= $p ?>&estado=<?= urlencode($filtro) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/footer_admin.php'; ?>
