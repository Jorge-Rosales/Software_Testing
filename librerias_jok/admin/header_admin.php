<?php
// Admin shared header - include after check_admin
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($adminTitle) ? htmlspecialchars($adminTitle) . ' — Admin JOK' : 'Admin — LIBRERIAS JOK' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/librerias_jok/public/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar">
  <div class="logo-wrap">
    <span class="logo-jok">JOK</span>
    <span class="logo-sub">Panel de Admin</span>
  </div>

  <nav class="mt-3 flex-grow-1">
    <div class="admin-nav-label">Principal</div>
    <a href="/librerias_jok/admin/dashboard.php"
       class="admin-nav-item <?= $currentPage==='dashboard.php'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="/librerias_jok/admin/libros.php"
       class="admin-nav-item <?= in_array($currentPage,['libros.php','crear_libro.php'])?'active':'' ?>">
      <i class="bi bi-book"></i> Libros
    </a>
    <a href="/librerias_jok/admin/ordenes.php"
       class="admin-nav-item <?= $currentPage==='ordenes.php'?'active':'' ?>">
      <i class="bi bi-bag-check"></i> Órdenes
    </a>
    <div class="admin-nav-label mt-2">Gestión</div>
    <a href="/librerias_jok/admin/devoluciones.php"
       class="admin-nav-item <?= $currentPage==='devoluciones.php'?'active':'' ?>">
      <i class="bi bi-arrow-return-left"></i> Devoluciones
    </a>
    <a href="/librerias_jok/admin/usuarios.php"
       class="admin-nav-item <?= $currentPage==='usuarios.php'?'active':'' ?>">
      <i class="bi bi-people"></i> Usuarios
    </a>
    <div class="admin-nav-label mt-2">Tienda</div>
    <a href="/librerias_jok/pages/catalogo.php" class="admin-nav-item" target="_blank">
      <i class="bi bi-shop"></i> Ver tienda
    </a>
  </nav>

  <div style="padding:1.5rem;border-top:1px solid rgba(212,175,55,0.2);">
    <a href="/librerias_jok/admin/logout.php" class="admin-nav-item text-danger" style="border-left:none;">
      <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>
  </div>
</aside>

<!-- Main content wrapper -->
<div class="admin-content">
  <div class="admin-topbar">
    <h1><?= isset($adminTitle) ? htmlspecialchars($adminTitle) : 'Dashboard' ?></h1>
    <div class="d-flex align-items-center gap-2 text-muted small">
      <i class="bi bi-person-circle"></i>
      <?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin') ?>
    </div>
  </div>
  <div class="p-4">
