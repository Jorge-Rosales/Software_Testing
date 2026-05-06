<?php
session_start();
if (isset($_SESSION['usuario_id'])) { header('Location: /librerias_jok/pages/catalogo.php'); exit; }

require_once __DIR__ . '/../includes/db.php';
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if (empty($nombre) || strlen($nombre) < 2) {
        $error = 'El nombre debe tener al menos 2 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Ese correo ya está registrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nombre, $email, $hash);
            if ($stmt->execute()) {
                $_SESSION['usuario_id'] = $db->insert_id;
                $_SESSION['nombre']     = $nombre;
                header('Location: /librerias_jok/pages/catalogo.php');
                exit;
            } else {
                $error = 'Error al registrar. Intenta de nuevo.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — LIBRERIAS JOK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&family=Cinzel:wght@400;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/librerias_jok/public/css/style.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #2d2420 100%); min-height:100vh; display:flex; align-items:center; justify-content:center;">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="auth-card">
        <div class="auth-logo">
          <span class="logo-jok">JOK</span>
          <span class="logo-sub">Crear cuenta</span>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label">Nombre completo</label>
            <input type="text" name="nombre" class="form-control"
                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                   placeholder="Tu nombre" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="tu@correo.com" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="password2" class="form-control" placeholder="Repite tu contraseña" required>
          </div>
          <button type="submit" class="btn btn-gold w-100 py-2">Crear cuenta</button>
        </form>

        <hr class="my-4">
        <p class="text-center text-muted small mb-0">
          ¿Ya tienes cuenta?
          <a href="/librerias_jok/pages/login.php" class="text-decoration-none" style="color:var(--gold-dark);">Inicia sesión</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
