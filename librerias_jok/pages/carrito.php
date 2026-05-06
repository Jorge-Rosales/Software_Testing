<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Mi Carrito';
$db  = getDB();
$uid = $_SESSION['usuario_id'];

$stmt = $db->prepare("
    SELECT c.id, c.cantidad, c.libro_id,
           l.titulo, l.autor, l.precio, l.stock, l.imagen,
           cat.nombre AS categoria_nombre
    FROM carrito c
    JOIN libros l ON l.id = c.libro_id
    LEFT JOIN categorias cat ON cat.id = l.categoria_id
    WHERE c.usuario_id = ?
    ORDER BY c.fecha_agregado DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
        <li class="breadcrumb-item active">Mi Carrito</li>
      </ol>
    </nav>
    <h1>Mi <span>Carrito</span></h1>
  </div>
</div>

<div class="container pb-5">
  <?php if (empty($items)): ?>
    <div class="empty-state">
      <i class="bi bi-cart3"></i>
      <h4>Tu carrito está vacío</h4>
      <p class="text-muted">Explora nuestro catálogo y agrega tus libros favoritos.</p>
      <a href="/librerias_jok/pages/catalogo.php" class="btn btn-gold">Ver catálogo</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
          <table class="table cart-table mb-0">
            <thead>
              <tr>
                <th>Libro</th>
                <th class="text-center">Cantidad</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
              <?php $sub = $it['precio'] * $it['cantidad']; ?>
              <tr id="cart-row-<?= $it['libro_id'] ?>">
                <!-- Book info -->
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <?php
                    $coverPalettes = [
                      'Ficción'    => ['#1a2540','#2d3f6b','📚'],
                      'No ficción' => ['#1a2d1a','#2a4a2a','🌍'],
                      'Autoayuda'  => ['#2d1a2d','#4a2a4a','✨'],
                      'Tecnología' => ['#0d1f2d','#1a3a4a','💻'],
                      'Historia'   => ['#2d2010','#4a3520','🏛️'],
                      'Ciencia'    => ['#0d2d2d','#1a4a4a','🔬'],
                      'Arte'       => ['#2d1010','#4a2020','🎨'],
                      'Infantil'   => ['#2d2010','#4a3a10','🌟'],
                    ];
                    $cp = $coverPalettes[$it['categoria_nombre'] ?? ''] ?? ['#1a1a2e','#2d2d4a','📖'];
                    ?>
                    <?php if ($it['imagen']): ?>
                      <img src="/librerias_jok/uploads/<?= htmlspecialchars($it['imagen']) ?>"
                           style="width:44px;height:60px;object-fit:cover;border-radius:6px;flex-shrink:0;" alt="">
                    <?php else: ?>
                      <div style="width:44px;height:60px;border-radius:6px;flex-shrink:0;overflow:hidden;
                           background:linear-gradient(160deg,<?= $cp[0] ?> 0%,<?= $cp[1] ?> 100%);
                           display:flex;flex-direction:column;align-items:center;justify-content:center;
                           padding:3px;text-align:center;position:relative;">
                        <div style="position:absolute;left:0;top:0;bottom:0;width:3px;background:rgba(212,175,55,0.5);"></div>
                        <div style="font-size:1.1rem;line-height:1;"><?= $cp[2] ?></div>
                        <div style="font-size:0.38rem;color:#fff;font-family:'Playfair Display',serif;
                             font-weight:700;line-height:1.2;margin-top:2px;overflow:hidden;
                             display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                          <?= htmlspecialchars($it['titulo']) ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <div>
                      <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:0.95rem;">
                        <?= htmlspecialchars($it['titulo']) ?>
                      </div>
                      <div class="text-muted" style="font-size:0.8rem;font-style:italic;">
                        <?= htmlspecialchars($it['autor']) ?>
                      </div>
                    </div>
                  </div>
                </td>
                <!-- Qty -->
                <td class="text-center">
                  <div class="cart-qty-group">
                    <button class="qty-btn" type="button"
                      onclick="cartChangeQty(<?= $it['libro_id'] ?>, -1, <?= $it['stock'] ?>, this)">
                      <i class="bi bi-dash"></i>
                    </button>
                    <input type="number" value="<?= $it['cantidad'] ?>" min="1" max="<?= $it['stock'] ?>"
                           class="qty-input" id="qty-<?= $it['libro_id'] ?>"
                           onchange="cartSetQty(<?= $it['libro_id'] ?>, parseInt(this.value), <?= $it['stock'] ?>, this)">
                    <button class="qty-btn" type="button"
                      onclick="cartChangeQty(<?= $it['libro_id'] ?>, 1, <?= $it['stock'] ?>, this)">
                      <i class="bi bi-plus"></i>
                    </button>
                  </div>
                </td>
                <!-- Price -->
                <td class="text-end" style="font-weight:700;">
                  $<?= number_format($it['precio'], 2) ?>
                </td>
                <!-- Subtotal -->
                <td class="text-end">
                  <span id="sub-<?= $it['libro_id'] ?>" class="row-subtotal fw-bold"
                        data-raw="<?= $sub ?>" data-price="<?= $it['precio'] ?>">
                    $<?= number_format($sub, 2) ?>
                  </span>
                </td>
                <!-- Remove -->
                <td class="text-end">
                  <button class="btn btn-sm text-danger border-0 p-1"
                    onclick="removeCartItem(<?= $it['libro_id'] ?>, this.closest('tr'))"
                    title="Eliminar">
                    <i class="bi bi-trash3"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <a href="/librerias_jok/pages/catalogo.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Seguir comprando
          </a>
        </div>
      </div>

      <!-- Summary -->
      <div class="col-lg-4">
        <div class="cart-summary">
          <h5>Resumen del pedido</h5>
          <div class="summary-row">
            <span>Subtotal</span>
            <span id="summary-subtotal">$<?= number_format($subtotal, 2) ?></span>
          </div>
          <div class="summary-row">
            <span>IVA (16%)</span>
            <span id="summary-iva">$<?= number_format($iva, 2) ?></span>
          </div>
          <div class="summary-total">
            <span>Total</span>
            <span id="summary-total">$<?= number_format($total, 2) ?></span>
          </div>
          <a href="/librerias_jok/pages/checkout.php" class="btn btn-gold w-100 py-2 mt-4">
            <i class="bi bi-credit-card me-2"></i>Proceder al pago
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function cartChangeQty(libroId, delta, maxStock, btn) {
  const inp = document.getElementById('qty-' + libroId);
  let val = parseInt(inp.value) + delta;
  if (val < 1) { showToast('Cantidad mínima: 1', 'error'); return; }
  if (val > maxStock) { showToast('Stock disponible: ' + maxStock, 'error'); return; }
  inp.value = val;
  doUpdateQty(libroId, val);
}
function cartSetQty(libroId, val, maxStock, inp) {
  if (val < 1) val = 1;
  if (val > maxStock) { val = maxStock; showToast('Stock disponible: ' + maxStock, 'error'); }
  inp.value = val;
  doUpdateQty(libroId, val);
}
async function doUpdateQty(libroId, qty) {
  const subCell = document.getElementById('sub-' + libroId);
  const price   = parseFloat(subCell.dataset.price);
  const res  = await fetch('/librerias_jok/api/actualizar_carrito.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ libro_id: libroId, cantidad: qty })
  });
  const data = await res.json();
  if (data.success) {
    const newSub = price * qty;
    subCell.dataset.raw = newSub;
    subCell.textContent = '$' + newSub.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
    updateCartBadge(data.cart_total);
    recalcCartSummary();
  } else {
    showToast(data.error || 'Error', 'error');
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
