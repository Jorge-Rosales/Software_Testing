<?php
require_once __DIR__ . '/../includes/check_admin.php';
require_once __DIR__ . '/../includes/db.php';
$adminTitle = 'Usuarios';
$db  = getDB();
$msg = '';

// Toggle activo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid  = (int)$_POST['toggle_id'];
    $stmt = $db->prepare("UPDATE usuarios SET activo = IF(activo=1,0,1) WHERE id = ? AND email != 'admin@librerias.com'");
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $stmt->close();
    header('Location: /librerias_jok/admin/usuarios.php?msg=ok');
    exit;
}

$msg = $_GET['msg'] === 'ok' ? 'Usuario actualizado.' : '';

$page    = max(1,(int)($_GET['page']??1));
$perPage = 15;
$total   = $db->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0];
$totalPages = max(1, ceil($total/$perPage));
$page   = min($page, $totalPages);
$offset = ($page-1)*$perPage;

$usuarios = $db->query("
    SELECT u.*, COUNT(o.id) AS total_ordenes
    FROM usuarios u
    LEFT JOIN ordenes o ON o.usuario_id = u.id
    GROUP BY u.id
    ORDER BY u.fecha_registro DESC
    LIMIT $perPage OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// User orders modal data (last 5 orders per user)
include __DIR__ . '/header_admin.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 mb-4">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="bg-white rounded-3 shadow-sm overflow-hidden">
  <table class="table admin-table mb-0">
    <thead>
      <tr>
        <th>ID</th><th>Nombre</th><th>Email</th>
        <th>Registro</th><th>Órdenes</th><th>Estado</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td class="text-muted small"><?= $u['id'] ?></td>
        <td class="fw-bold"><?= htmlspecialchars($u['nombre']) ?></td>
        <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
        <td class="text-muted small"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-secondary"
            onclick="verOrdenes(<?= $u['id'] ?>, '<?= addslashes($u['nombre']) ?>')">
            <?= $u['total_ordenes'] ?> pedido<?= $u['total_ordenes']!==1?'s':'' ?>
          </button>
        </td>
        <td>
          <?= $u['activo']
            ? '<span class="badge-green">Activo</span>'
            : '<span class="badge-red">Inactivo</span>' ?>
        </td>
        <td>
          <?php if ($u['email'] !== 'admin@librerias.com'): ?>
          <form method="POST" class="d-inline">
            <input type="hidden" name="toggle_id" value="<?= $u['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $u['activo'] ? 'btn-outline-danger':'btn-outline-success' ?>">
              <?= $u['activo'] ? '<i class="bi bi-person-slash"></i> Desactivar' : '<i class="bi bi-person-check"></i> Activar' ?>
            </button>
          </form>
          <?php else: ?>
            <span class="badge-gold">Admin</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Orders Modal -->
<div class="modal fade" id="ordersModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border:1px solid rgba(212,175,55,0.3);border-radius:14px;">
      <div class="modal-header" style="background:var(--black-soft);border-radius:14px 14px 0 0;">
        <h5 class="modal-title text-white" style="font-family:'Playfair Display',serif;">
          Pedidos de <span id="modal-user-name" style="color:var(--gold);"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modal-orders-body">
        <div class="text-center py-3"><span class="spinner-border text-warning"></span></div>
      </div>
    </div>
  </div>
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

<?php
$adminExtraJS = <<<JS
<script>
const ordersModal = new bootstrap.Modal(document.getElementById('ordersModal'));
async function verOrdenes(uid, nombre) {
  document.getElementById('modal-user-name').textContent = nombre;
  document.getElementById('modal-orders-body').innerHTML =
    '<div class="text-center py-3"><span class="spinner-border text-warning"></span></div>';
  ordersModal.show();
  const res  = await fetch('/librerias_jok/admin/api_user_orders.php?uid=' + uid);
  const data = await res.json();
  if (!data.ordenes || !data.ordenes.length) {
    document.getElementById('modal-orders-body').innerHTML =
      '<p class="text-muted text-center py-3">Sin pedidos registrados.</p>';
    return;
  }
  let html = '<table class="table table-sm mb-0"><thead><tr><th>#</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead><tbody>';
  data.ordenes.forEach(o => {
    const badge = o.estado==='completada'
      ? '<span class="badge-green">Completada</span>'
      : '<span class="badge-red">Cancelada</span>';
    html += \`<tr>
      <td class="fw-bold" style="color:var(--gold-dark);">#\${String(o.id).padStart(5,'0')}</td>
      <td class="text-muted small">\${o.fecha}</td>
      <td class="fw-bold">\$\${parseFloat(o.total).toLocaleString('es-MX',{minimumFractionDigits:2})}</td>
      <td>\${badge}</td>
    </tr>\`;
  });
  html += '</tbody></table>';
  document.getElementById('modal-orders-body').innerHTML = html;
}
</script>
JS;
include __DIR__ . '/footer_admin.php';
?>
