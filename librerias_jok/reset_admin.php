<?php
require_once __DIR__ . '/includes/db.php';
$db   = getDB();
$email = 'admin@librerias.com';
$pass  = 'Admin123';
$hash  = password_hash($pass, PASSWORD_DEFAULT);
$nombre = 'Administrador JOK';

// Borrar y recrear limpio
$db->query("DELETE FROM usuarios WHERE email = '$email'");
$stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash, activo) VALUES (?, ?, ?, 1)");
$stmt->bind_param('sss', $nombre, $email, $hash);
$ok = $stmt->execute();
$stmt->close();

// Verificar que funciona
$stmt = $db->prepare("SELECT id, password_hash, activo FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

$verify = $u && password_verify($pass, $u['password_hash']);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Reset Admin</title></head>
<body style="font-family:sans-serif;padding:2rem;">
<?php if ($ok && $verify): ?>
  <h2 style="color:green">✅ Admin creado y verificado correctamente</h2>
  <p>ID: <strong><?= $u['id'] ?></strong></p>
  <p>Email: <strong><?= $email ?></strong></p>
  <p>Contraseña: <strong><?= $pass ?></strong></p>
  <p>Activo: <strong><?= $u['activo'] ?></strong></p>
  <p>Hash verificado: <strong>SÍ</strong></p>
  <br>
  <a href="/librerias_jok/admin/login.php" style="background:#D4AF37;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;">
    → Ir al Login Admin
  </a>
<?php else: ?>
  <h2 style="color:red">❌ Error</h2>
  <p>ok: <?= $ok ? 'true' : 'false' ?></p>
  <p>verify: <?= $verify ? 'true' : 'false' ?></p>
  <p>user: <?= json_encode($u) ?></p>
  <p>mysql error: <?= $db->error ?></p>
<?php endif; ?>
<br><br>
<p style="color:red;font-weight:bold;">⚠️ Elimina este archivo después de usarlo: reset_admin.php</p>
</body></html>
