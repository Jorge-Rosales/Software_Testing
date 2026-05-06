<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Devoluciones';
$db  = getDB();
$uid = $_SESSION['usuario_id'];

// Completed orders with their items
$stmt = $db->prepare("
    SELECT o.id AS orden_id, o.fecha, o.total,
           oi.libro_id, oi.cantidad AS cant_comprada, oi.precio_unitario,
           l.titulo, l.autor, l.imagen
    FROM ordenes o
    JOIN orden_items oi ON oi.orden_id = o.id
    JOIN libros l ON l.id = oi.libro_id
    WHERE o.usuario_id = ? AND o.estado = 'completada'
    ORDER BY o.fecha DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by order
$ordenes = [];
foreach ($rows as $r) {
    $oid = $r['orden_id'];
    if (!isset($ordenes[$oid])) {
        $ordenes[$oid] = [
            'id'    => $oid,
            'fecha' => $r['fecha'],
            'total' => $r['total'],
            'items' => []
        ];
    }
    $ordenes[$oid]['items'][] = $r;
}

// Get existing returns for this user
$stmt = $db->prepare("SELECT orden_id, libro_id FROM devoluciones WHERE usuario_id = ? AND estado != 'rechazada'");
$stmt->bind_param('i', $uid);
$stmt->execute();
$devs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$devSet = [];
foreach ($devs as $d) $devSet[$d['orden_id'] . '_' . $d['libro_id']] = true;

// My returns list
$stmt = $db->prepare("
    SELECT d.*, l.titulo, o.id AS orden_num
    FROM devoluciones d
    JOIN libros l ON l.id = d.libro_id
    JOIN ordenes o ON o.id = d.orden_id
    WHERE d.usuario_id = ?
    ORDER BY d.fecha DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$misDevs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Pre-select order from ?orden=
$preOrden = (int)($_GET['orden'] ?? 0);

include __DIR__ . '/../includes/header.php';
?>

<div class="jok-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/librerias_jok/pages/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item active">Devoluciones</li>
      </ol>
    </nav>
    <h1>Mis <span>Devoluciones</span></h1>
  </div>
</div>

<div class="container pb-5">
  <div class="row g-4">

    <!-- Request return -->
    <div class="col-lg-7">
      <h5 class="mb-3" style="font-family:'Playfair Display',serif;">Solicitar devolución</h5>
      <?php if (empty($ordenes)): ?>
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h4>Sin pedidos completados</h4>
          <p class="text-muted">Necesitas al menos un pedido completado para solicitar una devolución.</p>
        </div>
      <?php else: ?>
        <?php foreach ($ordenes as $orden): ?>
        <div class="order-card mb-3">
          <div class="order-card-header">
            <div>
              <span class="order-num">#<?= str_pad($orden['id'],6,'0',STR_PAD_LEFT) ?></span>
              <span class="text-muted ms-3 small"><?= date('d/m/Y', strtotime($orden['fecha'])) ?></span>
            </div>
            <span class="fw-bold" style="color:var(--gold);font-family:'Playfair Display',serif;">
              $<?= number_format($orden['total'], 2) ?>
            </span>
          </div>
          <div class="order-body">
            <?php foreach ($orden['items'] as $it): ?>
            <?php $key = $orden['id'] . '_' . $it['libro_id']; ?>
            <div class="d-flex gap-3 align-items-center py-2" style="border-bottom:1px solid #f5f5f0;">
              <?php if ($it['imagen']): ?>
                <img src="/librerias_jok/uploads/<?= htmlspecialchars($it['imagen']) ?>"
                     style="width:36px;height:50px;object-fit:cover;border-radius:4px;" alt="">
              <?php else: ?>
                <div style="width:36px;height:50px;background:#1a1a1a;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                  <i class="bi bi-book" style="color:var(--gold);font-size:1rem;"></i>
                </div>
              <?php endif; ?>
              <div class="flex-grow-1 small">
                <div class="fw-bold"><?= htmlspecialchars($it['titulo']) ?></div>
                <div class="text-muted">Cantidad: <?= $it['cant_comprada'] ?></div>
              </div>
              <?php if (isset($devSet[$key])): ?>
                <span class="badge-gold">Devolución solicitada</span>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary btn-devolver"
                  data-libro-id="<?= $it['libro_id'] ?>"
                  data-orden-id="<?= $orden['id'] ?>"
                  data-titulo="<?= htmlspecialchars($it['titulo'], ENT_QUOTES) ?>"
                  data-max-cant="<?= $it['cant_comprada'] ?>">
                  <i class="bi bi-arrow-return-left me-1"></i>Devolver
                </button>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- My returns list -->
    <div class="col-lg-5">
      <h5 class="mb-3" style="font-family:'Playfair Display',serif;">Estado de mis devoluciones</h5>
      <?php if (empty($misDevs)): ?>
        <div class="p-4 bg-white rounded-3 shadow-sm text-center text-muted small">
          No tienes devoluciones registradas.
        </div>
      <?php else: ?>
        <?php foreach ($misDevs as $d): ?>
        <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-bold small"><?= htmlspecialchars($d['titulo']) ?></div>
              <div class="text-muted" style="font-size:0.75rem;">
                Orden #<?= str_pad($d['orden_num'],6,'0',STR_PAD_LEFT) ?> &bull;
                <?= date('d/m/Y', strtotime($d['fecha'])) ?> &bull;
                Cant: <?= $d['cantidad'] ?>
              </div>
              <?php if ($d['motivo']): ?>
              <div class="text-muted mt-1" style="font-size:0.75rem;font-style:italic;">
                "<?= htmlspecialchars($d['motivo']) ?>"
              </div>
              <?php endif; ?>
            </div>
            <?php if ($d['estado'] === 'pendiente'): ?>
              <span class="badge-gold">Pendiente</span>
            <?php elseif ($d['estado'] === 'aprobada'): ?>
              <span class="badge-green">Aprobada</span>
            <?php else: ?>
              <span class="badge-red">Rechazada</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="devModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border:1px solid rgba(212,175,55,0.3);border-radius:14px;">
      <div class="modal-header" style="background:var(--black-soft);border-bottom:1px solid rgba(212,175,55,0.2);border-radius:14px 14px 0 0;">
        <h5 class="modal-title" style="font-family:'Playfair Display',serif;color:#fff;">Solicitar devolución</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">
          <strong id="dev-titulo"></strong>
          <span class="text-muted small d-block" id="dev-info"></span>
        </p>
        <div class="mb-3">
          <label class="form-label">Cantidad a devolver</label>
          <input type="number" id="dev-cant" class="form-control" min="1" value="1">
        </div>
        <div class="mb-3">
          <label class="form-label">Motivo (opcional)</label>
          <textarea id="dev-motivo" class="form-control" rows="3" placeholder="Describe el motivo..."></textarea>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,0.06);">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-gold" id="devConfirmBtn" onclick="confirmarDev()">
          <i class="bi bi-check-lg me-2"></i>Confirmar devolución
        </button>
      </div>
    </div>
  </div>
</div>

<script>
var devLibroId = 0, devOrdenId = 0, devMaxCant = 1;
var devModal = null;

document.addEventListener('DOMContentLoaded', function() {
  devModal = new bootstrap.Modal(document.getElementById('devModal'));

  // Botones Devolver via data-*
  document.querySelectorAll('.btn-devolver').forEach(function(btn) {
    btn.addEventListener('click', function() {
      openDevModal(
        this.dataset.libroId,
        this.dataset.ordenId,
        this.dataset.titulo,
        this.dataset.maxCant
      );
    });
  });
});

function openDevModal(libroId, ordenId, titulo, maxCant) {
  devLibroId = libroId;
  devOrdenId = ordenId;
  devMaxCant = maxCant;
  document.getElementById('dev-titulo').textContent = titulo;
  document.getElementById('dev-info').textContent   = 'Máximo a devolver: ' + maxCant + ' unidad(es)';
  document.getElementById('dev-cant').value = 1;
  document.getElementById('dev-cant').max   = maxCant;
  document.getElementById('dev-motivo').value = '';
  devModal.show();
}

function confirmarDev() {
  var cant   = parseInt(document.getElementById('dev-cant').value);
  var motivo = document.getElementById('dev-motivo').value.trim();
  if (cant < 1 || cant > devMaxCant) {
    showToast('Cantidad inválida (máx: ' + devMaxCant + ')', 'error');
    return;
  }
  var btn = document.getElementById('devConfirmBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

  fetch('/librerias_jok/api/crear_devolucion.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ libro_id: devLibroId, orden_id: devOrdenId, cantidad: cant, motivo: motivo })
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if (data.success) {
      devModal.hide();
      showToast('Devolución registrada exitosamente');
      setTimeout(function(){ location.reload(); }, 1200);
    } else {
      showToast(data.error || 'Error al registrar', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Confirmar devolución';
    }
  })
  .catch(function(){
    showToast('Error de conexión', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Confirmar devolución';
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
