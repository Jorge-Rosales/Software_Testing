// ── LIBRERIAS JOK — Cart Logic ──
// Note: Buy modal logic lives in catalogo.js (loaded only on catalog page)

// ── Cart page: remove item ──
async function removeCartItem(libroId, row) {
  if (!confirm('¿Eliminar este libro del carrito?')) return;
  const res  = await fetch('/librerias_jok/api/eliminar_carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ libro_id: libroId })
  });
  const data = await res.json();
  if (data.success) {
    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    row.style.opacity    = '0';
    row.style.transform  = 'translateX(30px)';
    setTimeout(() => {
      row.remove();
      updateCartBadge(data.cart_total);
      recalcCartSummary();
      if (data.cart_total === 0) setTimeout(() => location.reload(), 300);
    }, 320);
    showToast('Libro eliminado del carrito');
  } else {
    showToast(data.error || 'Error al eliminar', 'error');
  }
}

// ── Cart page: change qty with +/- ──
function cartChangeQty(libroId, delta, maxStock, btn) {
  const inp = document.getElementById('qty-' + libroId);
  let val = parseInt(inp.value) + delta;
  if (val < 1) { showToast('Cantidad mínima: 1', 'error'); return; }
  if (val > maxStock) { showToast('Stock disponible: ' + maxStock, 'error'); return; }
  inp.value = val;
  doUpdateQty(libroId, val);
}

// ── Cart page: set qty directly ──
function cartSetQty(libroId, val, maxStock, inp) {
  if (isNaN(val) || val < 1) val = 1;
  if (val > maxStock) { val = maxStock; showToast('Stock disponible: ' + maxStock, 'error'); }
  inp.value = val;
  doUpdateQty(libroId, val);
}

async function doUpdateQty(libroId, qty) {
  const subCell = document.getElementById('sub-' + libroId);
  const price   = parseFloat(subCell.dataset.price);
  const res  = await fetch('/librerias_jok/api/actualizar_carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ libro_id: libroId, cantidad: qty })
  });
  const data = await res.json();
  if (data.success) {
    const newSub = price * qty;
    subCell.dataset.raw = newSub;
    subCell.textContent = formatMXN(newSub);
    updateCartBadge(data.cart_total);
    recalcCartSummary();
  } else {
    showToast(data.error || 'Error', 'error');
  }
}

function recalcCartSummary() {
  let subtotal = 0;
  document.querySelectorAll('.row-subtotal').forEach(cell => {
    subtotal += parseFloat(cell.dataset.raw || 0) || 0;
  });
  const iva   = subtotal * 0.16;
  const total = subtotal + iva;
  const el = id => document.getElementById(id);
  if (el('summary-subtotal')) el('summary-subtotal').textContent = formatMXN(subtotal);
  if (el('summary-iva'))      el('summary-iva').textContent      = formatMXN(iva);
  if (el('summary-total'))    el('summary-total').textContent    = formatMXN(total);
}
