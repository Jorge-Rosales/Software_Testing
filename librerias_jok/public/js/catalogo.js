// ── LIBRERIAS JOK — Catalog + Buy Modal ──

// ── Cover palette por categoría ──
const COVER_STYLES = {
  'Ficción':    { bg: 'linear-gradient(160deg,#1a2540 0%,#2d3f6b 60%,#1a1a2e 100%)', icon: '📚' },
  'No ficción': { bg: 'linear-gradient(160deg,#1a2d1a 0%,#2a4a2a 60%,#0f1f0f 100%)', icon: '🌍' },
  'Autoayuda':  { bg: 'linear-gradient(160deg,#2d1a2d 0%,#4a2a4a 60%,#1f0f1f 100%)', icon: '✨' },
  'Tecnología': { bg: 'linear-gradient(160deg,#0d1f2d 0%,#1a3a4a 60%,#0a1520 100%)', icon: '💻' },
  'Historia':   { bg: 'linear-gradient(160deg,#2d2010 0%,#4a3520 60%,#1f1508 100%)', icon: '🏛️' },
  'Ciencia':    { bg: 'linear-gradient(160deg,#0d2d2d 0%,#1a4a4a 60%,#081f1f 100%)', icon: '🔬' },
  'Arte':       { bg: 'linear-gradient(160deg,#2d1010 0%,#4a2020 60%,#1f0808 100%)', icon: '🎨' },
  'Infantil':   { bg: 'linear-gradient(160deg,#2d2010 0%,#4a3a10 60%,#1f1800 100%)', icon: '🌟' },
};
const COVER_DEFAULT = { bg: 'linear-gradient(160deg,#1a1a2e 0%,#2d2d4a 60%,#0f0f1e 100%)', icon: '📖' };

// ── Estado del modal ──
var _bm = { id: 0, precio: 0, stock: 0, titulo: '', autor: '', imagen: '', categoria: '' };

// ── Abrir modal ──
function openBuyModal(id, titulo, autor, precio, stock, imagen, categoria) {
  _bm = { id: id, titulo: titulo, autor: autor, precio: parseFloat(precio), stock: parseInt(stock), imagen: imagen || '', categoria: categoria || '' };

  // Imagen o portada generada
  var imgWrap = document.getElementById('bm-img-wrap');
  if (imagen && imagen.trim() !== '') {
    imgWrap.innerHTML = '<img src="/librerias_jok/uploads/' + imagen + '" alt="" style="width:55px;height:75px;object-fit:cover;display:block;border-radius:4px;">';
  } else {
    var cs = COVER_STYLES[categoria] || COVER_DEFAULT;
    imgWrap.innerHTML =
      '<div style="width:55px;height:75px;background:' + cs.bg + ';display:flex;flex-direction:column;' +
      'align-items:center;justify-content:center;padding:4px;text-align:center;position:relative;overflow:hidden;border-radius:4px;">' +
      '<div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:rgba(212,175,55,0.5);"></div>' +
      '<div style="font-size:1.4rem;line-height:1;">' + cs.icon + '</div>' +
      '<div style="font-size:0.48rem;color:#fff;margin-top:3px;font-family:serif;font-weight:700;line-height:1.2;">' + _esc(titulo) + '</div>' +
      '</div>';
  }

  document.getElementById('bm-title').textContent  = titulo;
  document.getElementById('bm-author').textContent = autor;
  document.getElementById('bm-price').textContent  = '$' + _fmt(precio) + ' MXN';
  document.getElementById('bm-stock-warn').textContent = '';

  var qtyInput = document.getElementById('bm-qty');
  qtyInput.value = 1;
  qtyInput.max   = stock;

  _bmUpdateTotal();

  // Resetear botón
  var addBtn = document.getElementById('bm-add-btn');
  addBtn.disabled = false;
  addBtn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Agregar al carrito';
  addBtn.className = 'btn btn-gold w-100 py-2 mt-2';

  // Mostrar modal
  var modal = document.getElementById('buyModal');
  modal.style.display = 'block';
  modal.classList.add('show');
}

// ── Cerrar modal ──
function closeBuyModal() {
  var modal = document.getElementById('buyModal');
  modal.classList.remove('show');
  modal.style.display = 'none';
}

// ── Actualizar total ──
function _bmUpdateTotal() {
  var qty = _bmClampQty();
  document.getElementById('bm-total').textContent = '$' + _fmt(qty * _bm.precio) + ' MXN';
  var warn = document.getElementById('bm-stock-warn');
  warn.textContent = qty >= _bm.stock ? 'Máximo disponible: ' + _bm.stock + ' unidades' : '';
}

function _bmClampQty() {
  var input = document.getElementById('bm-qty');
  var v = parseInt(input.value, 10);
  if (isNaN(v) || v < 1) v = 1;
  if (v > _bm.stock) v = _bm.stock;
  input.value = v;
  return v;
}

function _fmt(n) {
  return parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function _esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Inicialización cuando el DOM esté listo ──
function _initCatalogo() {

  // Filtros
  var filterForm = document.getElementById('filterForm');
  if (filterForm) {
    filterForm.querySelectorAll('select, input[type="radio"]').forEach(function(el) {
      el.addEventListener('change', function() { filterForm.submit(); });
    });
    var priceTimer;
    filterForm.querySelectorAll('input[type="number"]').forEach(function(el) {
      el.addEventListener('input', function() {
        clearTimeout(priceTimer);
        priceTimer = setTimeout(function() { filterForm.submit(); }, 600);
      });
    });
  }

  // Animación de tarjetas
  document.querySelectorAll('.book-card').forEach(function(card, i) {
    card.style.animationDelay = (i * 0.07) + 's';
  });

  // ── Controles del modal ──
  var qtyInput = document.getElementById('bm-qty');
  if (!qtyInput) return; // no estamos en el catálogo

  document.getElementById('bm-minus').addEventListener('click', function() {
    var v = parseInt(qtyInput.value, 10) || 1;
    if (v > 1) { qtyInput.value = v - 1; _bmUpdateTotal(); }
  });

  document.getElementById('bm-plus').addEventListener('click', function() {
    var v = parseInt(qtyInput.value, 10) || 1;
    if (v < _bm.stock) { qtyInput.value = v + 1; _bmUpdateTotal(); }
  });

  qtyInput.addEventListener('input', function() { _bmUpdateTotal(); });
  qtyInput.addEventListener('blur',  function() { _bmClampQty(); _bmUpdateTotal(); });

  // ── Botón Agregar al carrito ──
  document.getElementById('bm-add-btn').addEventListener('click', function() {
    var qty    = _bmClampQty();
    var addBtn = document.getElementById('bm-add-btn');

    if (qty < 1 || qty > _bm.stock) {
      document.getElementById('bm-stock-warn').textContent = 'Cantidad inválida.';
      return;
    }

    addBtn.disabled = true;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';

    fetch('/librerias_jok/api/agregar_carrito.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ libro_id: _bm.id, cantidad: qty })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.error) {
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Agregar al carrito';
        document.getElementById('bm-stock-warn').textContent = '⚠ ' + data.error;
        return;
      }
      // Éxito
      addBtn.className = 'btn btn-success w-100 py-2 mt-2';
      addBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>¡Agregado al carrito!';

      // Actualizar badge del carrito en el navbar
      if (data.cart_total !== undefined) {
        var badge = document.querySelector('.cart-badge');
        var navLink = document.querySelector('.jok-nav-icon[href*="carrito"]');
        if (badge) {
          badge.textContent = data.cart_total;
        } else if (navLink && data.cart_total > 0) {
          var b = document.createElement('span');
          b.className = 'cart-badge';
          b.textContent = data.cart_total;
          navLink.appendChild(b);
        }
      }

      showToast('✓ "' + _bm.titulo + '" agregado al carrito');
      setTimeout(function() { closeBuyModal(); }, 1400);
    })
    .catch(function() {
      addBtn.disabled = false;
      addBtn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Agregar al carrito';
      document.getElementById('bm-stock-warn').textContent = '⚠ Error de conexión. Intenta de nuevo.';
    });
  });

  // Cerrar modal al hacer clic afuera
  document.addEventListener('click', function(e) {
    var modal = document.getElementById('buyModal');
    if (!modal || !modal.classList.contains('show')) return;
    if (!modal.contains(e.target) && !e.target.closest('[onclick*="openBuyModal"]')) {
      closeBuyModal();
    }
  });
}

// Ejecutar cuando el DOM esté listo (compatible con carga tardía de scripts)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', _initCatalogo);
} else {
  _initCatalogo();
}
