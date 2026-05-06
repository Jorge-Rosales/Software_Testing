<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// Get cart count
$cartCount = 0;
if (isset($_SESSION['usuario_id'])) {
    $db = getDB();
    $uid = $_SESSION['usuario_id'];
    $res = $db->query("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = $uid");
    $row = $res->fetch_assoc();
    $cartCount = (int)($row['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — LIBRERIAS JOK' : 'LIBRERIAS JOK' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Lato:wght@300;400;700&family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/librerias_jok/public/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark jok-navbar sticky-top">
  <div class="container-fluid px-4">

    <!-- Logo -->
    <a class="navbar-brand jok-logo" href="/librerias_jok/pages/catalogo.php">
      <span class="logo-text">LIBRERIAS</span>
      <span class="logo-jok">JOK</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <!-- Search bar center -->
      <div class="mx-auto search-wrapper">
        <div class="input-group">
          <input type="text" class="form-control search-input" id="navSearchInput" placeholder="Buscar título, autor..." autocomplete="off">
          <button class="btn btn-gold" type="button" id="navSearchBtn">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <div id="navSearchResults" class="search-dropdown"></div>
      </div>

      <!-- Right icons -->
      <ul class="navbar-nav ms-3 align-items-center gap-2">
        <?php if (isset($_SESSION['usuario_id'])): ?>
          <li class="nav-item">
            <a class="nav-link jok-nav-icon" href="/librerias_jok/pages/carrito.php" title="Carrito">
              <i class="bi bi-cart3 fs-5"></i>
              <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link jok-nav-icon dropdown-toggle" href="#" data-bs-toggle="dropdown" title="Perfil">
              <i class="bi bi-person-circle fs-5"></i>
              <span class="ms-1 d-none d-lg-inline small"><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end jok-dropdown">
              <li><a class="dropdown-item" href="/librerias_jok/pages/historial.php"><i class="bi bi-clock-history me-2"></i>Mis pedidos</a></li>
              <li><a class="dropdown-item" href="/librerias_jok/pages/devoluciones.php"><i class="bi bi-arrow-return-left me-2"></i>Devoluciones</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/librerias_jok/api/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-outline-gold btn-sm" href="/librerias_jok/pages/login.php">Iniciar sesión</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-gold btn-sm" href="/librerias_jok/pages/registro.php">Registrarse</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Notification toast -->
<div id="jokToast" class="jok-toast" style="display:none;"></div>

<main>
