<?php
require_once __DIR__ . '/../includes/check_admin.php';
require_once __DIR__ . '/../includes/db.php';
$adminTitle = 'Dashboard';
$db = getDB();

// Stats
$totalVentas  = $db->query("SELECT COALESCE(SUM(total),0) FROM ordenes WHERE estado='completada'")->fetch_row()[0];
$ventasMes    = $db->query("SELECT COALESCE(SUM(total),0) FROM ordenes WHERE estado='completada' AND MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")->fetch_row()[0];
$totalOrdenes = $db->query("SELECT COUNT(*) FROM ordenes WHERE estado='completada'")->fetch_row()[0];
$totalUsers   = $db->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetch_row()[0];
$pendDevs     = $db->query("SELECT COUNT(*) FROM devoluciones WHERE estado='pendiente'")->fetch_row()[0];

// Top 5 books
$top5 = $db->query("
    SELECT l.titulo, l.autor, SUM(oi.cantidad) AS vendidos, SUM(oi.cantidad * oi.precio_unitario) AS ingresos
    FROM orden_items oi
    JOIN libros l ON l.id = oi.libro_id
    JOIN ordenes o ON o.id = oi.orden_id
    WHERE o.estado = 'completada'
    GROUP BY oi.libro_id
    ORDER BY vendidos DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recentOrders = $db->query("
    SELECT o.id, o.fecha, o.total, o.estado, u.nombre
    FROM ordenes o
    JOIN usuarios u ON u.id = o.usuario_id
    ORDER BY o.fecha DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/header_admin.php';
?>

<!-- Stats row -->
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-value">$<?= number_format($totalVentas, 0) ?></div>
      <div class="stat-label">Ventas totales</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-value">$<?= number_format($ventasMes, 0) ?></div>
      <div class="stat-label">Ventas este mes</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-value"><?= $totalOrdenes ?></div>
      <div class="stat-label">Órdenes completadas</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card" style="border-left-color:<?= $pendDevs > 0 ? '#ef4444' : 'var(--gold)' ?>">
      <div class="stat-value"><?= $pendDevs ?></div>
      <div class="stat-label">Devoluciones pendientes</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Top 5 books -->
  <div class="col-lg-5">
    <div class="bg-white rounded-3 shadow-sm p-4">
      <h5 class="mb-3" style="font-family:'Playfair Display',serif;">Top 5 libros más vendidos</h5>
      <?php if (empty($top5)): ?>
        <p class="text-muted small">Sin ventas registradas aún.</p>
      <?php else: ?>
        <?php foreach ($top5 as $i => $book): ?>
        <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid #f5f5f5;">
          <div style="width:28px;height:28px;background:var(--gold);color:#000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;">
            <?= $i + 1 ?>
          </div>
          <div class="flex-grow-1">
            <div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($book['titulo']) ?></div>
            <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($book['autor']) ?></div>
          </div>
          <div class="text-end">
            <div class="fw-bold small"><?= $book['vendidos'] ?> uds.</div>
            <div class="text-muted" style="font-size:0.72rem;">$<?= number_format($book['ingresos'], 0) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent orders -->
  <div class="col-lg-7">
    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
      <div class="p-4 pb-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0" style="font-family:'Playfair Display',serif;">Órdenes recientes</h5>
          <a href="/librerias_jok/admin/ordenes.php" class="btn btn-sm btn-outline-secondary">Ver todas</a>
        </div>
      </div>
      <table class="table admin-table mb-0">
        <thead><tr><th>#</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
        <tbody>
          <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td class="fw-bold" style="color:var(--gold-dark);">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($o['nombre']) ?></td>
            <td class="text-muted small"><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
            <td class="fw-bold">$<?= number_format($o['total'], 2) ?></td>
            <td>
              <?= $o['estado']==='completada'
                ? '<span class="badge-green">Completada</span>'
                : '<span class="badge-red">Cancelada</span>' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer_admin.php'; ?>
