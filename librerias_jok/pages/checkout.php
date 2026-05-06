<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Checkout';
$db  = getDB();
$uid = $_SESSION['usuario_id'];

$stmt = $db->prepare("
    SELECT c.cantidad, l.titulo, l.autor, l.precio, l.stock, l.imagen, l.id AS libro_id
    FROM carrito c
    JOIN libros l ON l.id = c.libro_id
    WHERE c.usuario_id = ?
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) {
    header('Location: /librerias_jok/pages/carrito.php');
    exit;
}

$subtotal = 0;
foreach ($items as $it) $subtotal += $it['precio'] * $it['cantidad'];
$iva   = $subtotal * 0.16;
$total = $subtotal + $iva;

include __DIR__ . '/../includes/header.php';
?>

<div class="jok-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/librerias_jok/pages/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item"><a href="/librerias_jok/pages/carrito.php">Carrito</a></li>
        <li class="breadcrumb-item active">Checkout</li>
      </ol>
    </nav>
    <h1>Confirmar <span>Pedido</span></h1>
  </div>
</div>

<div class="container pb-5">
  <div class="row g-4">

    <!-- Order summary (read-only) -->
    <div class="col-lg-7">
      <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
        <h5 class="mb-3" style="font-family:'Playfair Display',serif;">Resumen del pedido</h5>
        <?php foreach ($items as $it): ?>
        <div class="d-flex gap-3 align-items-center py-3" style="border-bottom:1px solid #f0f0f0;">
          <?php if ($it['imagen']): ?>
            <img src="/librerias_jok/uploads/<?= htmlspecialchars($it['imagen']) ?>"
                 style="width:48px;height:64px;object-fit:cover;border-radius:6px;" alt="">
          <?php else: ?>
            <div style="width:48px;height:64px;background:#1a1a1a;border-radius:6px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-book" style="color:var(--gold);font-size:1.3rem;"></i>
            </div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <div style="font-family:'Playfair Display',serif;font-weight:700;"><?= htmlspecialchars($it['titulo']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($it['autor']) ?></div>
            <div class="small">Cantidad: <strong><?= $it['cantidad'] ?></strong> &times; $<?= number_format($it['precio'], 2) ?></div>
          </div>
          <div class="fw-bold">$<?= number_format($it['precio'] * $it['cantidad'], 2) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Delivery address form -->
      <div class="bg-white rounded-3 shadow-sm p-4">
        <h5 class="mb-3" style="font-family:'Playfair Display',serif;">Dirección de entrega</h5>
        <form id="checkoutForm">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Calle y número</label>
              <input type="text" name="calle" id="f-calle" class="form-control" placeholder="Ej. Av. Insurgentes 123" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Colonia</label>
              <input type="text" name="colonia" id="f-colonia" class="form-control" placeholder="Colonia" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ciudad</label>
              <input type="text" name="ciudad" id="f-ciudad" class="form-control" placeholder="Ciudad" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <input type="text" name="estado" id="f-estado" class="form-control" placeholder="Estado" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Código postal</label>
              <input type="text" name="cp" id="f-cp" class="form-control" placeholder="00000" maxlength="5" required>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Payment summary -->
    <div class="col-lg-5">
      <div class="cart-summary mb-4">
        <h5>Total a pagar</h5>
        <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($subtotal, 2) ?></span></div>
        <div class="summary-row"><span>IVA (16%)</span><span>$<?= number_format($iva, 2) ?></span></div>
        <div class="summary-row"><span>Envío</span><span style="color:#22c55e;">Gratis</span></div>
        <div class="summary-total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>
      </div>

      <div class="bg-white rounded-3 shadow-sm p-4">
        <h6 class="mb-3 text-muted" style="font-size:0.8rem;letter-spacing:0.1em;text-transform:uppercase;">Pago simulado</h6>
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-2" style="background:#f5f5f0;border:1px dashed #ccc;">
          <i class="bi bi-shield-check" style="color:#22c55e;font-size:1.5rem;"></i>
          <div class="small text-muted">Este es un entorno de demostración. No se procesará ningún cargo real.</div>
        </div>
        <button id="payBtn" class="btn btn-gold w-100 py-3" style="font-size:1.05rem;">
          <i class="bi bi-credit-card me-2"></i>Pagar $<?= number_format($total, 2) ?>
        </button>
        <a href="/librerias_jok/pages/carrito.php" class="btn btn-outline-secondary w-100 mt-2 btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Regresar al carrito
        </a>
      </div>
    </div>

  </div>
</div>

<script>
document.getElementById('payBtn').addEventListener('click', async function() {
  const form = document.getElementById('checkoutForm');
  const fields = ['f-calle','f-colonia','f-ciudad','f-estado','f-cp'];
  let valid = true;
  fields.forEach(id => {
    const el = document.getElementById(id);
    if (!el.value.trim()) { el.classList.add('is-invalid'); valid = false; }
    else el.classList.remove('is-invalid');
  });
  if (!valid) { showToast('Por favor completa la dirección de entrega.', 'error'); return; }

  const direccion = [
    document.getElementById('f-calle').value,
    document.getElementById('f-colonia').value,
    document.getElementById('f-ciudad').value,
    document.getElementById('f-estado').value,
    'C.P. ' + document.getElementById('f-cp').value
  ].join(', ');

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

  try {
    const res  = await fetch('/librerias_jok/api/procesar_pago.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ direccion })
    });
    const data = await res.json();
    if (data.success) {
      window.location = '/librerias_jok/pages/confirmacion.php?orden=' + data.orden_id;
    } else {
      showToast(data.error || 'Error al procesar el pago.', 'error');
      this.disabled = false;
      this.innerHTML = '<i class="bi bi-credit-card me-2"></i>Pagar $<?= number_format($total, 2) ?>';
    }
  } catch(e) {
    showToast('Error de conexión.', 'error');
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-credit-card me-2"></i>Pagar $<?= number_format($total, 2) ?>';
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
