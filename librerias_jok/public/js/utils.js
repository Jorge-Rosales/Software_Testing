// ── LIBRERIAS JOK — Utils ──

function showToast(msg, type) {
  type = type || 'success';
  var t = document.getElementById('jokToast');
  if (!t) return;
  var icon  = type === 'success' ? '✓' : '✗';
  var color = type === 'success' ? '#22c55e' : '#ef4444';
  t.style.borderColor = color;
  t.innerHTML = '<span style="color:'+color+';margin-right:8px;font-weight:700;">'+icon+'</span>'+msg;
  t.style.display = 'block';
  setTimeout(function(){ t.style.display = 'none'; }, 3000);
}

function formatMXN(amount) {
  return '$' + parseFloat(amount).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateCartBadge(count) {
  var badge = document.querySelector('.cart-badge');
  var icon  = document.querySelector('.jok-nav-icon[href*="carrito"] i');
  if (count > 0) {
    if (badge) {
      badge.textContent = count;
    } else if (icon) {
      var a = icon.closest('a');
      var b = document.createElement('span');
      b.className = 'cart-badge';
      b.textContent = count;
      a.appendChild(b);
    }
  } else {
    if (badge) badge.remove();
  }
}

// ── Navbar live search ──
(function() {
  var input   = document.getElementById('navSearchInput');
  var results = document.getElementById('navSearchResults');
  var btn     = document.getElementById('navSearchBtn');
  if (!input || !results) return;

  var timer;
  input.addEventListener('input', function() {
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { results.style.display = 'none'; results.innerHTML = ''; return; }
    timer = setTimeout(function(){ doSearch(q); }, 280);
  });

  btn.addEventListener('click', function() {
    var q = input.value.trim();
    if (q) window.location = '/librerias_jok/pages/catalogo.php?q=' + encodeURIComponent(q);
  });

  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') btn.click();
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-wrapper')) { results.style.display = 'none'; }
  });

  var SEARCH_COVERS = {
    'Ficción':    ['#1a2540','#2d3f6b','📚'],
    'No ficción': ['#1a2d1a','#2a4a2a','🌍'],
    'Autoayuda':  ['#2d1a2d','#4a2a4a','✨'],
    'Tecnología': ['#0d1f2d','#1a3a4a','💻'],
    'Historia':   ['#2d2010','#4a3520','🏛️'],
    'Ciencia':    ['#0d2d2d','#1a4a4a','🔬'],
    'Arte':       ['#2d1010','#4a2020','🎨'],
    'Infantil':   ['#2d2010','#4a3a10','🌟']
  };

  function searchCoverHtml(libro) {
    if (libro.imagen) {
      return '<img src="/librerias_jok/uploads/'+libro.imagen+'" style="width:36px;height:50px;object-fit:cover;border-radius:4px;flex-shrink:0;">';
    }
    var cat = libro.categoria_nombre || libro.categoria || '';
    var cp  = SEARCH_COVERS[cat] || ['#1a1a2e','#2d2d4a','📖'];
    return '<div style="width:36px;height:50px;border-radius:4px;flex-shrink:0;overflow:hidden;'+
      'background:linear-gradient(160deg,'+cp[0]+','+cp[1]+');'+
      'display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2px;text-align:center;position:relative;">'+
      '<div style="position:absolute;left:0;top:0;bottom:0;width:3px;background:rgba(212,175,55,0.4);"></div>'+
      '<div style="font-size:1rem;line-height:1;">'+cp[2]+'</div></div>';
  }

  function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function doSearch(q) {
    fetch('/librerias_jok/api/buscar_libros.php?q=' + encodeURIComponent(q) + '&limit=5')
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (!data.libros || !data.libros.length) { results.style.display = 'none'; return; }
      results.innerHTML = data.libros.map(function(l){
        return '<a class="search-result-item" href="/librerias_jok/pages/catalogo.php?q='+encodeURIComponent(l.titulo)+'">'+
          searchCoverHtml(l)+
          '<div><div class="search-result-title">'+escHtml(l.titulo)+'</div>'+
          '<div class="search-result-author">'+escHtml(l.autor)+'</div></div></a>';
      }).join('');
      results.style.display = 'block';
    })
    .catch(function(){ results.style.display = 'none'; });
  }
})();
