<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Mis Pedidos';
$db  = getDB();
$uid = $_SESSION['usuario_id'];

$stmt = $db->prepare("SELECT * FROM ordenes WHERE usuario_id = ? ORDER BY fecha DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$ordenes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get items for all orders
$orderIds = array_column($ordenes, 'id');
$itemsMap = [];
if ($orderIds) {
    $placeholders = implode(',', $orderIds);
    $res = $db->query("
        SELECT oi.*, l.titulo, l.autor, l.imagen, c.nombre AS categoria_nombre
        FROM orden_items oi
        JOIN libros l ON l.id = oi.libro_id
        LEFT JOIN categorias c ON c.id = l.categoria_id
        WHERE oi.orden_id IN ($placeholders)
    ");
    while ($row = $res->fetch_assoc()) {
        $itemsMap[$row['orden_id']][] = $row;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="jok-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/librerias_jok/pages/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item active">Mis Pedidos</li>
      </ol>
    </nav>
    <h1>Mis <span>Pedidos</span></h1>
    <p class="text-muted mb-0"><?= count($ordenes) ?> pedido<?= count($ordenes) !== 1 ? 's' : '' ?> registrado<?= count($ordenes) !== 1 ? 's' : '' ?></p>
  </div>
</div>

<div class="container pb-5">
  <?php if (empty($ordenes)): ?>
    <div class="empty-state">
      <i class="bi bi-bag-x"></i>
      <h4>Sin pedidos aún</h4>
      <p class="text-muted">Haz tu primera compra y aparecerá aquí.</p>
      <a href="/librerias_jok/pages/catalogo.php" class="btn btn-gold">Ver catálogo</a>
    </div>
  <?php else: ?>
    <?php foreach ($ordenes as $orden): ?>
    <?php $oid = $orden['id']; $items = $itemsMap[$oid] ?? []; ?>
    <div class="order-card">
      <div class="order-card-header">
        <div>
          <span class="order-num">#<?= str_pad($oid, 6, '0', STR_PAD_LEFT) ?></span>
          <span class="text-muted ms-3 small"><?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="fw-bold" style="color:var(--gold);font-family:'Playfair Display',serif;">
            $<?= number_format($orden['total'], 2) ?>
          </span>
          <?php if ($orden['estado'] === 'completada'): ?>
            <span class="badge-green">Completada</span>
          <?php else: ?>
            <span class="badge-red">Cancelada</span>
          <?php endif; ?>
          <button class="btn btn-sm btn-outline-light" style="font-size:0.75rem;"
            onclick="toggleOrder(<?= $oid ?>)">
            <i class="bi bi-chevron-down" id="chevron-<?= $oid ?>"></i>
          </button>
        </div>
      </div>

      <div class="order-body" id="order-body-<?= $oid ?>" style="display:none;">
        <?php foreach ($items as $it): ?>
        <div class="d-flex gap-3 align-items-center py-2" style="border-bottom:1px solid #f5f5f0;">
          <?php if ($it['imagen']): ?>
            <img src="/librerias_jok/uploads/<?= htmlspecialchars($it['imagen']) ?>"
                 style="width:36px;height:50px;object-fit:cover;border-radius:4px;flex-shrink:0;" alt="">
          <?php else:
            $hcp = [
              'Ficción'=>['#1a2540','#2d3f6b','📚'], 'No ficción'=>['#1a2d1a','#2a4a2a','🌍'],
              'Autoayuda'=>['#2d1a2d','#4a2a4a','✨'], 'Tecnología'=>['#0d1f2d','#1a3a4a','💻'],
              'Historia'=>['#2d2010','#4a3520','🏛️'], 'Ciencia'=>['#0d2d2d','#1a4a4a','🔬'],
              'Arte'=>['#2d1010','#4a2020','🎨'], 'Infantil'=>['#2d2010','#4a3a10','🌟'],
            ];
            $hc = $hcp[$it['categoria_nombre'] ?? ''] ?? ['#1a1a2e','#2d2d4a','📖'];
          ?>
            <div style="width:36px;height:50px;border-radius:4px;flex-shrink:0;overflow:hidden;
                 background:linear-gradient(160deg,<?= $hc[0] ?> 0%,<?= $hc[1] ?> 100%);
                 display:flex;flex-direction:column;align-items:center;justify-content:center;
                 padding:2px;text-align:center;position:relative;">
              <div style="position:absolute;left:0;top:0;bottom:0;width:3px;background:rgba(212,175,55,0.5);"></div>
              <div style="font-size:1rem;line-height:1;"><?= $hc[2] ?></div>
            </div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($it['titulo']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($it['autor']) ?></div>
          </div>
          <div class="text-muted small">x<?= $it['cantidad'] ?></div>
          <div class="fw-bold small">$<?= number_format($it['precio_unitario'] * $it['cantidad'], 2) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
          <div class="text-muted small">
            Subtotal: $<?= number_format($orden['subtotal'],2) ?> &nbsp;|&nbsp;
            IVA: $<?= number_format($orden['iva'],2) ?> &nbsp;|&nbsp;
            <strong>Total: $<?= number_format($orden['total'],2) ?></strong>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-gold"
              onclick="recomprar(<?= $oid ?>)" <?= $orden['estado'] !== 'completada' ? 'disabled' : '' ?>>
              <i class="bi bi-arrow-repeat me-1"></i>Recomprar
            </button>
            <?php if ($orden['estado'] === 'completada'): ?>
            <a href="/librerias_jok/pages/devoluciones.php?orden=<?= $oid ?>" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-arrow-return-left me-1"></i>Devolver
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
function toggleOrder(id) {
  const body    = document.getElementById('order-body-' + id);
  const chevron = document.getElementById('chevron-' + id);
  const open    = body.style.display === 'block';
  body.style.display = open ? 'none' : 'block';
  chevron.className  = open ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}

async function recomprar(ordenId) {
  const btn = event.target.closest('button');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  const res  = await fetch('/librerias_jok/api/agregar_carrito.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ recomprar_orden_id: ordenId })
  });
  const data = await res.json();
  if (data.success) {
    updateCartBadge(data.cart_total);
    showToast('¡Artículos añadidos al carrito!');
    setTimeout(() => window.location = '/librerias_jok/pages/carrito.php', 1200);
  } else {
    showToast(data.error || 'Error al recomprar', 'error');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Recomprar';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
