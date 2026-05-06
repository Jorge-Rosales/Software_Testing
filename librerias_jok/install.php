<?php
// ─────────────────────────────────────────────
//  LIBRERIAS JOK — Instalador
//  Accede a: http://localhost/librerias_jok/install.php
//  ELIMINA ESTE ARCHIVO después de la instalación.
// ─────────────────────────────────────────────

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'librerias_jok';

$log = [];
$ok  = true;

function step($msg) { global $log; $log[] = ['ok', $msg]; }
function fail($msg) { global $log, $ok; $log[] = ['err', $msg]; $ok = false; }

// Connect (no DB yet)
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("ERROR: No se pudo conectar a MySQL: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Create DB
if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    step("Base de datos '$db' creada.");
} else {
    fail("Error creando DB: " . $conn->error);
}

$conn->select_db($db);

// ─── TABLES ───────────────────────────────────
$tables = [
"CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT DEFAULT 1
)",
"CREATE TABLE IF NOT EXISTS categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
)",
"CREATE TABLE IF NOT EXISTS libros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    autor VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categoria_id INT NOT NULL,
    imagen VARCHAR(255),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
)",
"CREATE TABLE IF NOT EXISTS carrito (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    libro_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_libro (usuario_id, libro_id)
)",
"CREATE TABLE IF NOT EXISTS ordenes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('completada','cancelada') DEFAULT 'completada',
    direccion TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS orden_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    libro_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libros(id)
)",
"CREATE TABLE IF NOT EXISTS devoluciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    libro_id INT NOT NULL,
    orden_id INT NOT NULL,
    cantidad INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT,
    estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libros(id),
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS resenas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    libro_id INT NOT NULL,
    calificacion INT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_libro_resena (usuario_id, libro_id)
)"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) step("Tabla creada/verificada.");
    else fail("Error creando tabla: " . $conn->error);
}

// ─── CATEGORIES ───────────────────────────────
$cats = ['Ficción','No ficción','Tecnología','Autoayuda','Infantil'];
foreach ($cats as $cat) {
    $stmt = $conn->prepare("INSERT IGNORE INTO categorias (nombre) VALUES (?)");
    $stmt->bind_param('s', $cat);
    if ($stmt->execute()) step("Categoría '$cat' insertada.");
    else fail("Error insertando categoría '$cat': " . $stmt->error);
    $stmt->close();
}

// Get category IDs
$catIds = [];
$res = $conn->query("SELECT id, nombre FROM categorias");
while ($r = $res->fetch_assoc()) $catIds[$r['nombre']] = $r['id'];

// ─── BOOKS ────────────────────────────────────
$libros = [
    ['Cien años de soledad',    'Gabriel García Márquez', 'La saga de la familia Buendía a lo largo de siete generaciones en el mítico pueblo de Macondo.',               550.00, 20, $catIds['Ficción'],    ],
    ['El principito',           'Antoine de Saint-Exupéry','Un piloto encuentra a un pequeño príncipe caído de un asteroide. Una historia sobre la infancia y los sueños.', 180.00, 35, $catIds['Ficción'],    ],
    ['Sapiens',                 'Yuval Noah Harari',      'Cómo Homo sapiens llegó a dominar el mundo. Un recorrido por la historia de la humanidad.',                    480.00, 18, $catIds['No ficción'], ],
    ['El poder del ahora',      'Eckhart Tolle',          'Una guía para la iluminación espiritual y la vida en el momento presente.',                                    320.00, 25, $catIds['Autoayuda'],  ],
    ['Clean Code',              'Robert C. Martin',       'Manual de buen estilo en el desarrollo de software. Imprescindible para todo programador.',                    750.00, 10, $catIds['Tecnología'], ],
    ['Harry Potter y la piedra filosofal', 'J.K. Rowling','El primer libro de la saga de un joven mago que descubre sus poderes en Hogwarts.',                           280.00, 40, $catIds['Ficción'],    ],
    ['Hábitos atómicos',        'James Clear',            'Cómo los pequeños cambios producen resultados extraordinarios. Sistema práctico para construir buenos hábitos.',395.00, 22, $catIds['Autoayuda'],  ],
    ['El mundo de Sofía',       'Jostein Gaarder',        'Una novela filosófica que narra la historia de la filosofía occidental de manera accesible.',                  310.00, 15, $catIds['Ficción'],    ],
    ['Python Crash Course',     'Eric Matthes',           'Introducción práctica y accesible al lenguaje de programación Python.',                                        680.00, 12, $catIds['Tecnología'], ],
    ['El Alquimista',           'Paulo Coelho',           'La historia de Santiago, un pastor andaluz en busca de su tesoro personal.',                                   220.00, 30, $catIds['Ficción'],    ],
    ['Inteligencia emocional',  'Daniel Goleman',         'Por qué puede importar más que el cociente intelectual en el trabajo y en la vida personal.',                  370.00, 20, $catIds['Autoayuda'],  ],
    ['Design Patterns',         'Gang of Four',           'Los 23 patrones de diseño clásicos para el desarrollo orientado a objetos.',                                   820.00,  8, $catIds['Tecnología'], ],
    ['Matilda',                 'Roald Dahl',             'La historia de una niña con poderes extraordinarios que enfrenta a la terrible directora Trunchbull.',         160.00, 45, $catIds['Infantil'],   ],
    ['Factfulness',             'Hans Rosling',           'Diez razones por las que estamos equivocados sobre el mundo y por qué las cosas van mejor de lo que piensas.',  430.00, 14, $catIds['No ficción'], ],
    ['Eloquent JavaScript',     'Marijn Haverbeke',       'Introducción moderna a la programación con JavaScript para la web.',                                            590.00, 11, $catIds['Tecnología'], ],
    ['Don Quijote de la Mancha','Miguel de Cervantes',    'La primera novela moderna: las aventuras del hidalgo Alonso Quijano y su escudero Sancho Panza.',              450.00, 16, $catIds['Ficción'],    ],
    ['El hobbit',               'J.R.R. Tolkien',         'Las aventuras de Bilbo Bolsón, un hobbit que se embarca en una inesperada odisea.',                            260.00, 28, $catIds['Ficción'],    ],
    ['Breve historia del tiempo','Stephen Hawking',       'Una exploración de los grandes interrogantes del universo: el Big Bang, los agujeros negros y el tiempo.',     340.00, 19, $catIds['No ficción'], ],
    ['Charlie y la fábrica de chocolate','Roald Dahl',    'Charlie Bucket gana un boleto dorado para visitar la fábrica del excéntrico señor Willy Wonka.',              155.00, 38, $catIds['Infantil'],   ],
    ['The Pragmatic Programmer', 'David Thomas & Andrew Hunt','Consejos atemporales para convertirse en un mejor desarrollador de software.',                             710.00,  9, $catIds['Tecnología'], ],
];

$insStmt = $conn->prepare("INSERT IGNORE INTO libros (titulo, autor, descripcion, precio, stock, categoria_id) VALUES (?,?,?,?,?,?)");
foreach ($libros as $l) {
    $insStmt->bind_param('sssdii', $l[0], $l[1], $l[2], $l[3], $l[4], $l[5]);
    if ($insStmt->execute()) step("Libro insertado: {$l[0]}");
    else fail("Error insertando libro {$l[0]}: " . $insStmt->error);
}
$insStmt->close();

// ─── ADMIN USER ───────────────────────────────
$adminEmail = 'admin@librerias.com';
$adminPass  = password_hash('Admin123', PASSWORD_DEFAULT);
$adminNom   = 'Administrador JOK';
$stmt = $conn->prepare("INSERT IGNORE INTO usuarios (nombre, email, password_hash) VALUES (?,?,?)");
$stmt->bind_param('sss', $adminNom, $adminEmail, $adminPass);
if ($stmt->execute()) step("Usuario admin creado: admin@librerias.com / Admin123");
else fail("Error creando admin: " . $stmt->error);
$stmt->close();

// ─── Create uploads dir ───────────────────────
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) step("Carpeta /uploads creada.");
    else fail("Error creando /uploads");
} else {
    step("Carpeta /uploads ya existe.");
}

// Create .htaccess for uploads (allow images only)
file_put_contents($uploadsDir . '/.htaccess', "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>\n");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Instalación — LIBRERIAS JOK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { background: linear-gradient(135deg, #0f0f0f, #2d2420); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Lato', sans-serif; margin: 0; }
    .card { background: #1a1a1a; border: 1px solid rgba(212,175,55,0.3); border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 580px; box-shadow: 0 16px 64px rgba(0,0,0,0.5); }
    h1 { font-family: 'Cinzel', serif; color: #D4AF37; font-size: 1.8rem; text-align: center; margin-bottom: 0.3rem; }
    .sub { text-align: center; color: rgba(255,255,255,0.4); font-size: 0.8rem; letter-spacing: 0.2em; margin-bottom: 2rem; text-transform: uppercase; }
    .log { background: #0f0f0f; border-radius: 8px; padding: 1rem; max-height: 280px; overflow-y: auto; margin-bottom: 1.5rem; }
    .log-item { font-size: 0.82rem; padding: 0.2rem 0; display: flex; gap: 0.5rem; }
    .log-ok  { color: #22c55e; } .log-ok::before  { content: '✓'; }
    .log-err { color: #ef4444; } .log-err::before { content: '✗'; }
    .result { text-align: center; padding: 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; }
    .result.success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; }
    .result.error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #ef4444; }
    .credentials { background: rgba(212,175,55,0.08); border: 1px solid rgba(212,175,55,0.25); border-radius: 10px; padding: 1.2rem; margin-bottom: 1.5rem; }
    .credentials h3 { color: #D4AF37; font-size: 0.85rem; letter-spacing: 0.15em; text-transform: uppercase; font-family: 'Cinzel', serif; margin-bottom: 0.75rem; }
    .cred-row { display: flex; justify-content: space-between; color: rgba(255,255,255,0.8); font-size: 0.88rem; margin-bottom: 0.3rem; }
    .cred-val { color: #D4AF37; font-weight: 700; font-family: monospace; }
    .btns { display: flex; gap: 1rem; flex-wrap: wrap; }
    .btn { display: inline-block; padding: 0.7rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.9rem; text-align: center; flex: 1; }
    .btn-gold { background: #D4AF37; color: #0f0f0f; }
    .btn-outline { background: transparent; border: 1.5px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.7); }
    .warn { color: rgba(255,255,255,0.4); font-size: 0.78rem; text-align: center; margin-top: 1.2rem; }
  </style>
</head>
<body>
<div class="card">
  <h1>LIBRERIAS JOK</h1>
  <div class="sub">Instalación del sistema</div>

  <div class="log">
    <?php foreach ($log as $entry): ?>
    <div class="log-item log-<?= $entry[0] ?>">
      <?= htmlspecialchars($entry[1]) ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="result <?= $ok ? 'success' : 'error' ?>">
    <?= $ok
      ? '✓ Instalación completada exitosamente'
      : '✗ Se encontraron errores durante la instalación' ?>
  </div>

  <?php if ($ok): ?>
  <div class="credentials">
    <h3>Credenciales de acceso</h3>
    <div class="cred-row">
      <span>Admin email:</span>
      <span class="cred-val">admin@librerias.com</span>
    </div>
    <div class="cred-row">
      <span>Admin contraseña:</span>
      <span class="cred-val">Admin123</span>
    </div>
    <div class="cred-row" style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid rgba(212,175,55,0.2);">
      <span>Base de datos:</span>
      <span class="cred-val">librerias_jok</span>
    </div>
  </div>

  <div class="btns">
    <a href="/librerias_jok/pages/login.php" class="btn btn-gold">Ir a la tienda</a>
    <a href="/librerias_jok/admin/login.php" class="btn btn-outline">Panel Admin</a>
  </div>
  <p class="warn">⚠ Elimina este archivo (install.php) después de la instalación.</p>
  <?php endif; ?>
</div>
</body>
</html>
