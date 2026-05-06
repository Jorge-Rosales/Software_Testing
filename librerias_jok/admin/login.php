<?php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: /librerias_jok/admin/dashboard.php'); exit; }

require_once __DIR__ . '/../includes/db.php';
$error = '';
define('ADMIN_EMAIL', 'admin@librerias.com');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($pass)) {
        $error = 'Completa todos los campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, nombre, password_hash FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user && $email === ADMIN_EMAIL && password_verify($pass, $user['password_hash'])) {
            $_SESSION['admin_id']     = $user['id'];
            $_SESSION['admin_nombre'] = $user['nombre'];
            header('Location: /librerias_jok/admin/dashboard.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — LIBRERIAS JOK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&family=Cinzel:wght@400;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/librerias_jok/public/css/style.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#0f0f0f 0%,#1a1a1a 50%,#2d2420 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="auth-card">
        <div class="auth-logo">
          <span class="logo-jok">JOK</span>
          <span class="logo-sub">Panel Administrador</span>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2 d-flex gap-2 align-items-center">
            <i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control" placeholder="admin@librerias.com" required autofocus>
          </div>
          <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn btn-gold w-100 py-2">Ingresar al panel</button>
        </form>
        <hr class="my-4">
        <p class="text-center mb-0">
          <a href="/librerias_jok/pages/login.php" class="text-muted small">← Tienda</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
