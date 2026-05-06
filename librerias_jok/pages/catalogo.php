<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Catálogo';
$db = getDB();

// ── Filters ──
$q        = trim($_GET['q']        ?? '');
$cat      = (int)($_GET['cat']     ?? 0);
$min      = (float)($_GET['min']   ?? 0);
$max      = (float)($_GET['max']   ?? 0);
$orden    = $_GET['orden']         ?? 'nombre';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 8;

$allowed_orders = ['precio_asc','precio_desc','nombre','popular'];
if (!in_array($orden, $allowed_orders)) $orden = 'nombre';

// ── Build query ──
$where  = ["l.stock >= 0"];
$params = [];
$types  = '';

if ($q !== '') {
    $where[]  = "(l.titulo LIKE ? OR l.autor LIKE ?)";
    $like     = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($cat > 0) {
    $where[]  = "l.categoria_id = ?";
    $params[] = $cat;
    $types   .= 'i';
}
if ($min > 0) {
    $where[]  = "l.precio >= ?";
    $params[] = $min;
    $types   .= 'd';
}
if ($max > 0) {
    $where[]  = "l.precio <= ?";
    $params[] = $max;
    $types   .= 'd';
}

$whereSQL = implode(' AND ', $where);

$orderMap = [
    'precio_asc'  => 'l.precio ASC',
    'precio_desc' => 'l.precio DESC',
    'nombre'      => 'l.titulo ASC',
    'popular'     => 'avg_cal DESC',
];
$orderSQL = $orderMap[$orden];

// Total count
$countSQL  = "SELECT COUNT(*) FROM libros l LEFT JOIN resenas r ON r.libro_id = l.id WHERE $whereSQL";
$countStmt = $db->prepare($countSQL);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();

$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Books
$sql = "SELECT l.*, c.nombre AS categoria_nombre,
               COALESCE(AVG(r.calificacion),0) AS avg_cal,
               COUNT(r.id) AS num_resenas
        FROM libros l
        LEFT JOIN categorias c ON c.id = l.categoria_id
        LEFT JOIN resenas r    ON r.libro_id = l.id
        WHERE $whereSQL
        GROUP BY l.id
        ORDER BY $orderSQL
        LIMIT ? OFFSET ?";

$params2 = array_merge($params, [$perPage, $offset]);
$types2  = $types . 'ii';
$stmt    = $db->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$libros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Categories for filter
$cats = $db->query("SELECT * FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Build query string helper
function buildQS($overrides) {
    $base = $_GET;
    foreach ($overrides as $k => $v) $base[$k] = $v;
    return '?' . http_build_query($base);
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<script>
// ── Definición temprana del modal (disponible para los botones onclick) ──
var COVER_STYLES = {
  'Ficción':    { bg:'linear-gradient(160deg,#1a2540,#2d3f6b,#1a1a2e)', icon:'📚' },
  'No ficción': { bg:'linear-gradient(160deg,#1a2d1a,#2a4a2a,#0f1f0f)', icon:'🌍' },
  'Autoayuda':  { bg:'linear-gradient(160deg,#2d1a2d,#4a2a4a,#1f0f1f)', icon:'✨' },
  'Tecnología': { bg:'linear-gradient(160deg,#0d1f2d,#1a3a4a,#0a1520)', icon:'💻' },
  'Historia':   { bg:'linear-gradient(160deg,#2d2010,#4a3520,#1f1508)', icon:'🏛️' },
  'Ciencia':    { bg:'linear-gradient(160deg,#0d2d2d,#1a4a4a,#081f1f)', icon:'🔬' },
  'Arte':       { bg:'linear-gradient(160deg,#2d1010,#4a2020,#1f0808)', icon:'🎨' },
  'Infantil':   { bg:'linear-gradient(160deg,#2d2010,#4a3a10,#1f1800)', icon:'🌟' }
};
var _bm = { id:0, precio:0, stock:0, titulo:'', autor:'', imagen:'', categoria:'' };

function openBuyModal(id, titulo, autor, precio, stock, imagen, categoria) {
  _bm = { id:id, titulo:titulo, autor:autor, precio:parseFloat(precio), stock:parseInt(stock), imagen:imagen||'', categoria:categoria||'' };

  var cs = COVER_STYLES[categoria] || { bg:'linear-gradient(160deg,#1a1a2e,#2d2d4a,#0f0f1e)', icon:'📖' };
  var imgWrap = document.getElementById('bm-img-wrap');
  if (imagen && imagen.trim() !== '') {
    imgWrap.innerHTML = '<img src="/librerias_jok/uploads/'+imagen+'" style="width:55px;height:75px;object-fit:cover;border-radius:4px;">';
  } else {
    imgWrap.innerHTML = '<div style="width:55px;height:75px;background:'+cs.bg+';border-radius:4px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px;text-align:center;position:relative;overflow:hidden;"><div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:rgba(212,175,55,0.5);"></div><div style="font-size:1.5rem;">'+cs.icon+'</div><div style="font-size:0.45rem;color:#fff;margin-top:2px;font-weight:700;">'+titulo.substring(0,30)+'</div></div>';
  }

  document.getElementById('bm-title').textContent  = titulo;
  document.getElementById('bm-author').textContent = autor;
  document.getElementById('bm-price').textContent  = '$' + _bmFmt(precio) + ' MXN';
  document.getElementById('bm-stock-warn').textContent = '';

  var qty = document.getElementById('bm-qty');
  qty.value = 1; qty.max = stock;
  _bmTotal();

  var btn = document.getElementById('bm-add-btn');
  btn.disabled = false;
  btn.className = 'btn btn-gold w-100 py-2 mt-2';
  btn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Agregar al carrito';

  var modal = document.getElementById('buyModal');
  modal.style.display = 'block';
  modal.classList.add('show');
}

function closeBuyModal() {
  var m = document.getElementById('buyModal');
  m.style.display = 'none';
  m.classList.remove('show');
}

function _bmFmt(n) {
  return parseFloat(n).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
}

function _bmClamp() {
  var el = document.getElementById('bm-qty');
  var v = parseInt(el.value,10); if(isNaN(v)||v<1) v=1; if(v>_bm.stock) v=_bm.stock; el.value=v; return v;
}

function _bmTotal() {
  var q = _bmClamp();
  document.getElementById('bm-total').textContent = '$'+_bmFmt(q*_bm.precio)+' MXN';
  document.getElementById('bm-stock-warn').textContent = q>=_bm.stock ? 'Máximo: '+_bm.stock+' unidades' : '';
}
</script>

<div class="jok-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/librerias_jok/pages/catalogo.php">Inicio</a></li>
        <li class="breadcrumb-item active">Catálogo</li>
      </ol>
    </nav>
    <h1>Nuestro <span>Catálogo</span></h1>
    <p class="text-muted mb-0"><?= $total ?> libro<?= $total !== 1 ? 's' : '' ?> disponible<?= $total !== 1 ? 's' : '' ?></p>
  </div>
</div>

<div class="container pb-5">
  <div class="row g-4">

    <!-- Filters sidebar -->
    <div class="col-lg-3">
      <div class="filter-panel">
        <form id="filterForm" method="GET">
          <input type="hidden" name="page" value="1">

          <!-- Search -->
          <h6>Buscar</h6>
          <div class="input-group mb-4">
            <input type="text" name="q" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($q) ?>" placeholder="Título o autor...">
            <button class="btn btn-gold btn-sm" type="submit"><i class="bi bi-search"></i></button>
          </div>

          <!-- Categories -->
          <h6>Categoría</h6>
          <div class="mb-4">
            <div class="form-check mb-1">
              <input class="form-check-input" type="radio" name="cat" value="0" id="catAll"
                     <?= $cat === 0 ? 'checked' : '' ?>>
              <label class="form-check-label" for="catAll">Todas</label>
            </div>
            <?php foreach ($cats as $c): ?>
            <div class="form-check mb-1">
              <input class="form-check-input" type="radio" name="cat" value="<?= $c['id'] ?>" id="cat<?= $c['id'] ?>"
                     <?= $cat === $c['id'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></label>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Price range -->
          <h6>Precio (MXN)</h6>
          <div class="row g-2 mb-4">
            <div class="col-6">
              <input type="number" name="min" class="form-control form-control-sm" placeholder="Mín"
                     value="<?= $min > 0 ? htmlspecialchars($min) : '' ?>" min="0">
            </div>
            <div class="col-6">
              <input type="number" name="max" class="form-control form-control-sm" placeholder="Máx"
                     value="<?= $max > 0 ? htmlspecialchars($max) : '' ?>" min="0">
            </div>
          </div>

          <!-- Sort -->
          <h6>Ordenar por</h6>
          <select name="orden" class="form-select form-select-sm mb-4">
            <option value="nombre"      <?= $orden==='nombre'      ? 'selected':'' ?>>Nombre A-Z</option>
            <option value="precio_asc"  <?= $orden==='precio_asc'  ? 'selected':'' ?>>Precio: menor a mayor</option>
            <option value="precio_desc" <?= $orden==='precio_desc' ? 'selected':'' ?>>Precio: mayor a menor</option>
            <option value="popular"     <?= $orden==='popular'     ? 'selected':'' ?>>Mejor calificados</option>
          </select>

          <button type="submit" class="btn btn-gold w-100 btn-sm">Aplicar filtros</button>
          <?php if ($q || $cat || $min || $max): ?>
          <a href="/librerias_jok/pages/catalogo.php" class="btn btn-outline-secondary w-100 btn-sm mt-2">Limpiar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Books grid -->
    <div class="col-lg-9">

      <?php if (empty($libros)): ?>
        <div class="empty-state">
          <i class="bi bi-book-half"></i>
          <h4>Sin resultados</h4>
          <p class="text-muted">No encontramos libros con esos filtros. Intenta con otros términos.</p>
          <a href="/librerias_jok/pages/catalogo.php" class="btn btn-gold">Ver todos los libros</a>
        </div>
      <?php else: ?>

        <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-xl-3">
          <?php foreach ($libros as $i => $libro): ?>
          <div class="col">
            <div class="book-card" style="animation-delay:<?= $i * 0.06 ?>s">
              <!-- Image -->
              <div class="book-card-img-wrap">
                <?php if ($libro['imagen'] && file_exists(__DIR__ . '/../uploads/' . $libro['imagen'])): ?>
                  <img src="/librerias_jok/uploads/<?= htmlspecialchars($libro['imagen']) ?>" alt="<?= htmlspecialchars($libro['titulo']) ?>">
                <?php else:
                  // Generate a unique cover based on category
                  $coverStyles = [
                    'Ficción'      => ['bg'=>'linear-gradient(160deg,#1a2540 0%,#2d3f6b 60%,#1a1a2e 100%)', 'icon'=>'📚', 'ornament'=>'✦'],
                    'No ficción'   => ['bg'=>'linear-gradient(160deg,#1a2d1a 0%,#2a4a2a 60%,#0f1f0f 100%)', 'icon'=>'🌍', 'ornament'=>'◈'],
                    'Autoayuda'    => ['bg'=>'linear-gradient(160deg,#2d1a2d 0%,#4a2a4a 60%,#1f0f1f 100%)', 'icon'=>'✨', 'ornament'=>'❋'],
                    'Tecnología'   => ['bg'=>'linear-gradient(160deg,#0d1f2d 0%,#1a3a4a 60%,#0a1520 100%)', 'icon'=>'💻', 'ornament'=>'◉'],
                    'Historia'     => ['bg'=>'linear-gradient(160deg,#2d2010 0%,#4a3520 60%,#1f1508 100%)', 'icon'=>'🏛️', 'ornament'=>'⊕'],
                    'Ciencia'      => ['bg'=>'linear-gradient(160deg,#0d2d2d 0%,#1a4a4a 60%,#081f1f 100%)', 'icon'=>'🔬', 'ornament'=>'⊗'],
                    'Arte'         => ['bg'=>'linear-gradient(160deg,#2d1010 0%,#4a2020 60%,#1f0808 100%)', 'icon'=>'🎨', 'ornament'=>'✿'],
                    'Infantil'     => ['bg'=>'linear-gradient(160deg,#2d2010 0%,#4a3a10 60%,#1f1800 100%)', 'icon'=>'🌟', 'ornament'=>'✧'],
                  ];
                  $cat_nombre = $libro['categoria_nombre'] ?? 'Ficción';
                  $style = $coverStyles[$cat_nombre] ?? ['bg'=>'linear-gradient(160deg,#1a1a2e 0%,#2d2d4a 60%,#0f0f1e 100%)', 'icon'=>'📖', 'ornament'=>'✦'];
                ?>
                  <div class="book-cover-generated" style="background:<?= $style['bg'] ?>">
                    <div class="book-cover-spine"></div>
                    <div class="book-cover-icon"><?= $style['icon'] ?></div>
                    <div class="book-cover-title-text"><?= htmlspecialchars($libro['titulo']) ?></div>
                    <div class="book-cover-author-text"><?= htmlspecialchars($libro['autor']) ?></div>
                    <div class="book-cover-ornament"><?= $style['ornament'] ?></div>
                  </div>
                <?php endif; ?>
                <!-- Stock badge -->
                <?php if ($libro['stock'] <= 0): ?>
                  <span class="stock-badge stock-out">Agotado</span>
                <?php elseif ($libro['stock'] <= 5): ?>
                  <span class="stock-badge stock-low">Pocas unidades</span>
                <?php else: ?>
                  <span class="stock-badge stock-ok">En stock</span>
                <?php endif; ?>
              </div>

              <!-- Body -->
              <div class="book-card-body">
                <div class="book-category"><?= htmlspecialchars($libro['categoria_nombre']) ?></div>
                <div class="book-title"><?= htmlspecialchars($libro['titulo']) ?></div>
                <div class="book-author"><?= htmlspecialchars($libro['autor']) ?></div>
                <div class="book-desc"><?= htmlspecialchars($libro['descripcion'] ?? '') ?></div>
                <!-- Stars -->
                <div class="d-flex align-items-center gap-2 mt-auto">
                  <div class="book-stars">
                    <?php $avg = round($libro['avg_cal']); for ($s=1;$s<=5;$s++) echo $s<=$avg ? '★' : '☆'; ?>
                  </div>
                  <span class="book-review-count">(<?= $libro['num_resenas'] ?>)</span>
                </div>
              </div>

              <!-- Footer -->
              <div class="book-card-footer">
                <span class="book-price">$<?= number_format($libro['precio'], 2) ?></span>
                 <?php if ($libro['stock'] > 0): ?>
                 <button class="btn btn-gold btn-sm btn-comprar"
                   data-id="<?= $libro['id'] ?>"
                   data-titulo="<?= htmlspecialchars($libro['titulo'], ENT_QUOTES) ?>"
                   data-autor="<?= htmlspecialchars($libro['autor'], ENT_QUOTES) ?>"
                   data-precio="<?= $libro['precio'] ?>"
                   data-stock="<?= $libro['stock'] ?>"
                   data-imagen="<?= htmlspecialchars($libro['imagen'] ?? '', ENT_QUOTES) ?>"
                   data-categoria="<?= htmlspecialchars($libro['categoria_nombre'] ?? '', ENT_QUOTES) ?>">
                   <i class="bi bi-cart-plus me-1"></i>Comprar
                 </button>
                 <?php else: ?>
                 <button class="btn btn-outline-secondary btn-sm" disabled>Agotado</button>
                 <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page<=1 ? 'disabled':'' ?>">
              <a class="page-link" href="<?= buildQS(['page'=>$page-1]) ?>">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
            <?php for ($p=1; $p<=$totalPages; $p++): ?>
            <li class="page-item <?= $p===$page ? 'active':'' ?>">
              <a class="page-link" href="<?= buildQS(['page'=>$p]) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page>=$totalPages ? 'disabled':'' ?>">
              <a class="page-link" href="<?= buildQS(['page'=>$page+1]) ?>">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Buy Modal (bottom-right) ── -->
<div id="buyModal" class="buy-modal">
  <button class="buy-modal-close" onclick="closeBuyModal()" type="button">&times;</button>
  <div class="buy-modal-header">
    <div id="bm-img-wrap" class="buy-modal-img" style="overflow:hidden;border-radius:6px;flex-shrink:0;">
      <!-- filled dynamically by JS -->
    </div>
    <div>
      <div id="bm-title" class="buy-modal-title"></div>
      <div id="bm-author" class="buy-modal-author"></div>
      <div id="bm-price" class="buy-modal-price"></div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-center gap-3 my-3">
    <button id="bm-minus" class="qty-btn" type="button"><i class="bi bi-dash"></i></button>
    <input id="bm-qty" type="number" class="qty-input" value="1" min="1">
    <button id="bm-plus" class="qty-btn" type="button"><i class="bi bi-plus"></i></button>
  </div>
  <div id="bm-stock-warn" class="text-warning text-center" style="font-size:0.75rem;min-height:1rem;margin-top:-0.5rem;margin-bottom:0.25rem;"></div>

  <div class="buy-modal-total">
    <span class="total-label">Total</span>
    <span id="bm-total" class="total-value"></span>
  </div>

  <button id="bm-add-btn" class="btn btn-gold w-100 py-2 mt-2">
    <i class="bi bi-cart-plus me-2"></i>Agregar al carrito
  </button>
</div>

<script>
// ── Eventos del modal (funciones definidas en el script del header) ──

document.addEventListener('DOMContentLoaded', function() {

  // ── Botones Comprar via data-* attributes ──
  document.querySelectorAll('.btn-comprar').forEach(function(btn) {
    btn.addEventListener('click', function() {
      openBuyModal(
        this.dataset.id,
        this.dataset.titulo,
        this.dataset.autor,
        this.dataset.precio,
        this.dataset.stock,
        this.dataset.imagen,
        this.dataset.categoria
      );
    });
  });

  // Filtros auto-submit
  var ff = document.getElementById('filterForm');
  if (ff) {
    ff.querySelectorAll('select,input[type="radio"]').forEach(function(el){
      el.addEventListener('change', function(){ ff.submit(); });
    });
    var pt;
    ff.querySelectorAll('input[type="number"]').forEach(function(el){
      el.addEventListener('input', function(){ clearTimeout(pt); pt=setTimeout(function(){ff.submit();},600); });
    });
  }

  // Animación tarjetas
  document.querySelectorAll('.book-card').forEach(function(c,i){
    c.style.animationDelay = (i*0.07)+'s';
  });

  var qtyEl = document.getElementById('bm-qty');
  if (!qtyEl) return;

  document.getElementById('bm-minus').addEventListener('click', function(){
    var v = parseInt(qtyEl.value)||1;
    if (v>1){ qtyEl.value=v-1; _bmTotal(); }
  });

  document.getElementById('bm-plus').addEventListener('click', function(){
    var v = parseInt(qtyEl.value)||1;
    if (v<_bm.stock){ qtyEl.value=v+1; _bmTotal(); }
  });

  qtyEl.addEventListener('input',  function(){ _bmTotal(); });
  qtyEl.addEventListener('blur',   function(){ _bmClamp(); _bmTotal(); });

  document.getElementById('bm-add-btn').addEventListener('click', function(){
    var qty = _bmClamp();
    var btn = document.getElementById('bm-add-btn');
    if (qty<1||qty>_bm.stock){ document.getElementById('bm-stock-warn').textContent='Cantidad inválida.'; return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';

    fetch('/librerias_jok/api/agregar_carrito.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ libro_id:_bm.id, cantidad:qty })
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (data.error){
        btn.disabled=false;
        btn.innerHTML='<i class="bi bi-cart-plus me-2"></i>Agregar al carrito';
        document.getElementById('bm-stock-warn').textContent='⚠ '+data.error;
        return;
      }
      btn.className='btn btn-success w-100 py-2 mt-2';
      btn.innerHTML='<i class="bi bi-check-lg me-2"></i>¡Agregado al carrito!';

      // Actualizar badge carrito
      if (data.cart_total !== undefined) {
        var badge = document.querySelector('.cart-badge');
        var navLink = document.querySelector('a[href*="carrito"]');
        if (badge) { badge.textContent = data.cart_total; }
        else if (navLink && data.cart_total>0) {
          var b=document.createElement('span'); b.className='cart-badge'; b.textContent=data.cart_total; navLink.appendChild(b);
        }
      }

      showToast('✓ Agregado al carrito');
      setTimeout(function(){ closeBuyModal(); }, 1400);
    })
    .catch(function(){
      btn.disabled=false;
      btn.innerHTML='<i class="bi bi-cart-plus me-2"></i>Agregar al carrito';
      document.getElementById('bm-stock-warn').textContent='⚠ Error de conexión.';
    });
  });

  // Cerrar al hacer clic afuera
  document.addEventListener('click', function(e){
    var modal=document.getElementById('buyModal');
    if (modal && modal.classList.contains('show') && !modal.contains(e.target) && !e.target.closest('.btn-comprar')){
      closeBuyModal();
    }
  });
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';

?>
