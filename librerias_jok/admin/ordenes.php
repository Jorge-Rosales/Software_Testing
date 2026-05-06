<?php
require_once __DIR__ . '/../includes/check_admin.php';
require_once __DIR__ . '/../includes/db.php';
$adminTitle = 'Órdenes';
$db = getDB();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$total      = $db->query("SELECT COUNT(*) FROM ordenes")->fetch_row()[0];
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);

$ordenes = $db->query("
    SELECT o.*, u.nombre AS cliente, u.email
    FROM ordenes o
    JOIN usuarios u ON u.id = o.usuario_id
    ORDER BY o.fecha DESC
    LIMIT $perPage OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Get items for displayed orders
$itemsMap = [];
if ($ordenes) {
    $ids = implode(',', array_column($ordenes, 'id'));
    $res = $db->query("
        SELECT oi.orden_id, oi.cantidad, oi.precio_unitario, l.titulo, l.autor
        FROM orden_items oi JOIN libros l ON l.id = oi.libro_id
        WHERE oi.orden_id IN ($ids)
    ");
    while ($r = $res->fetch_assoc()) $itemsMap[$r['orden_id']][] = $r;
}

include __DIR__ . '/header_admin.php';
?>

<div class="bg-white rounded-3 shadow-sm overflow-hidden">
  <table class="table admin-table mb-0">
    <thead>
      <tr>
        <th>#</th><th>Cliente</th><th>Fecha</th>
        <th>Subtotal</th><th>IVA</th><th>Total</th><th>Estado</th><th>Detalles</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ordenes as $o): ?>
      <tr>
        <td class="fw-bold" style="color:var(--gold-dark);">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></td>
        <td>
          <div class="fw-bold" style="font-size:0.88rem;"><?= htmlspecialchars($o['cliente']) ?></div>
          <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($o['email']) ?></div>
        </td>
        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($o['fecha'])) ?></td>
        <td class="small">$<?= number_format($o['subtotal'], 2) ?></td>
        <td class="small text-muted">$<?= number_format($o['iva'], 2) ?></td>
        <td class="fw-bold">$<?= number_format($o['total'], 2) ?></td>
        <td>
          <?= $o['estado']==='completada'
            ? '<span class="badge-green">Completada</span>'
            : '<span class="badge-red">Cancelada</span>' ?>
        </td>
        <td>
          <button class="btn btn-sm btn-outline-secondary"
            data-bs-toggle="collapse" data-bs-target="#items-<?= $o['id'] ?>">
            <i class="bi bi-list-ul"></i>
          </button>
        </td>
      </tr>
      <!-- Items collapse -->
      <tr class="collapse" id="items-<?= $o['id'] ?>">
        <td colspan="8" style="background:#fafafa;padding:0.75rem 1rem;">
          <?php $items = $itemsMap[$o['id']] ?? []; ?>
          <?php if (empty($items)): ?>
            <span class="text-muted small">Sin items.</span>
          <?php else: ?>
            <div class="d-flex flex-wrap gap-3">
              <?php foreach ($items as $it): ?>
              <div class="small p-2 rounded-2" style="background:#fff;border:1px solid #eee;">
                <strong><?= htmlspecialchars($it['titulo']) ?></strong>
                <span class="text-muted"> &bull; <?= htmlspecialchars($it['autor']) ?></span>
                <span class="ms-2">x<?= $it['cantidad'] ?> — $<?= number_format($it['precio_unitario'] * $it['cantidad'], 2) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($o['direccion']): ?>
            <div class="mt-2 text-muted small"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($o['direccion']) ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($ordenes)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Sin órdenes registradas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-4">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($p=1;$p<=$totalPages;$p++): ?>
    <li class="page-item <?= $p===$page?'active':'' ?>">
      <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/footer_admin.php'; ?>
