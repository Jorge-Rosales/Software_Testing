<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Confirmación de pedido';
$db  = getDB();
$uid = $_SESSION['usuario_id'];

$orden_id = (int)($_GET['orden'] ?? 0);
if (!$orden_id) { header('Location: /librerias_jok/pages/catalogo.php'); exit; }

// Verify order belongs to user
$stmt = $db->prepare("SELECT * FROM ordenes WHERE id = ? AND usuario_id = ?");
$stmt->bind_param('ii', $orden_id, $uid);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orden) { header('Location: /librerias_jok/pages/catalogo.php'); exit; }

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, l.titulo, l.autor, l.imagen
    FROM orden_items oi
    JOIN libros l ON l.id = oi.libro_id
    WHERE oi.orden_id = ?
");
$stmt->bind_param('i', $orden_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">

      <!-- Success header -->
      <div class="text-center mb-5" style="animation: fadeInDown 0.6s ease both;">
        <div style="width:80px;height:80px;background:rgba(34,197,94,0.1);border:2px solid rgba(34,197,94,0.4);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
          <i class="bi bi-check-lg" style="font-size:2rem;color:#22c55e;"></i>
        </div>
        <h1 style="font-family:'Playfair Display',serif;font-weight:900;font-size:2rem;">
          ¡Gracias por tu compra!
        </h1>
        <p class="text-muted">Tu pedido ha sido procesado exitosamente.</p>
        <div class="d-inline-block px-4 py-2 rounded-2" style="background:rgba(212,175,55,0.1);border:1px solid rgba(212,175,55,0.3);">
          <span class="text-muted small">Número de orden</span>
          <div style="font-family:'Cinzel',serif;font-size:1.3rem;font-weight:700;color:var(--gold);">
            #<?= str_pad($orden_id, 6, '0', STR_PAD_LEFT) ?>
          </div>
        </div>
      </div>

      <!-- Order details -->
      <div class="bg-white rounded-3 shadow-sm overflow-hidden mb-4">
        <div style="background:var(--black-soft);padding:1rem 1.5rem;">
          <span style="font-family:'Cinzel',serif;font-size:0.8rem;letter-spacing:0.12em;color:var(--gold);">DETALLE DEL PEDIDO</span>
        </div>
        <div class="p-4">
          <?php foreach ($items as $it): ?>
          <div class="d-flex gap-3 align-items-center py-3" style="border-bottom:1px solid #f0f0f0;">
            <?php if ($it['imagen']): ?>
              <img src="/librerias_jok/uploads/<?= htmlspecialchars($it['imagen']) ?>"
                   style="width:44px;height:60px;object-fit:cover;border-radius:6px;" alt="">
            <?php else: ?>
              <div style="width:44px;height:60px;background:#1a1a1a;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-book" style="color:var(--gold);font-size:1.1rem;"></i>
              </div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div style="font-family:'Playfair Display',serif;font-weight:700;"><?= htmlspecialchars($it['titulo']) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($it['autor']) ?></div>
              <div class="small">Cantidad: <?= $it['cantidad'] ?> &times; $<?= number_format($it['precio_unitario'], 2) ?></div>
            </div>
            <div class="fw-bold">$<?= number_format($it['precio_unitario'] * $it['cantidad'], 2) ?></div>
          </div>
          <?php endforeach; ?>

          <div class="mt-3">
            <div class="d-flex justify-content-between text-muted small py-1">
              <span>Subtotal</span><span>$<?= number_format($orden['subtotal'], 2) ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted small py-1">
              <span>IVA (16%)</span><span>$<?= number_format($orden['iva'], 2) ?></span>
            </div>
            <div class="d-flex justify-content-between fw-bold py-2" style="border-top:2px solid var(--gold);font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--gold);">
              <span>Total</span><span>$<?= number_format($orden['total'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Delivery info -->
      <?php if ($orden['direccion']): ?>
      <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
        <h6 style="font-family:'Cinzel',serif;font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold-dark);margin-bottom:0.5rem;">
          Dirección de entrega
        </h6>
        <p class="mb-0 text-muted"><?= htmlspecialchars($orden['direccion']) ?></p>
      </div>
      <?php endif; ?>

      <!-- Actions -->
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="/librerias_jok/pages/historial.php" class="btn btn-gold">
          <i class="bi bi-clock-history me-2"></i>Ver mis pedidos
        </a>
        <a href="/librerias_jok/pages/catalogo.php" class="btn btn-outline-secondary">
          <i class="bi bi-shop me-2"></i>Seguir comprando
        </a>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
