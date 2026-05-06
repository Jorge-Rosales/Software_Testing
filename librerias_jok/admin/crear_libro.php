<?php
require_once __DIR__ . '/../includes/check_admin.php';
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

$editId = (int)($_GET['edit'] ?? 0);
$libro  = null;
$adminTitle = $editId ? 'Editar libro' : 'Nuevo libro';

if ($editId) {
    $stmt = $db->prepare("SELECT * FROM libros WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $libro = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$libro) { header('Location: /librerias_jok/admin/libros.php'); exit; }
}

$cats  = $db->query("SELECT * FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']      ?? '');
    $autor       = trim($_POST['autor']       ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = (float)($_POST['precio']   ?? 0);
    $stock       = (int)($_POST['stock']      ?? 0);
    $cat_id      = (int)($_POST['categoria_id'] ?? 0);

    if (empty($titulo) || empty($autor) || $precio <= 0 || $stock < 0 || $cat_id <= 0) {
        $error = 'Completa todos los campos obligatorios correctamente.';
    } else {
        $imagen = $libro['imagen'] ?? null;

        // Handle image upload
        if (!empty($_FILES['imagen']['name'])) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext     = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Formato de imagen no válido.';
            } elseif ($_FILES['imagen']['size'] > 3 * 1024 * 1024) {
                $error = 'La imagen no debe superar 3MB.';
            } else {
                $filename = 'libro_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $dest     = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    // Remove old image
                    if ($imagen && file_exists(__DIR__ . '/../uploads/' . $imagen)) {
                        @unlink(__DIR__ . '/../uploads/' . $imagen);
                    }
                    $imagen = $filename;
                } else {
                    $error = 'Error al subir la imagen.';
                }
            }
        }

        if (empty($error)) {
            if ($editId) {
                $stmt = $db->prepare("UPDATE libros SET titulo=?, autor=?, descripcion=?, precio=?, stock=?, categoria_id=?, imagen=? WHERE id=?");
                $stmt->bind_param('sssdissi', $titulo, $autor, $descripcion, $precio, $stock, $cat_id, $imagen, $editId);
            } else {
                $stmt = $db->prepare("INSERT INTO libros (titulo, autor, descripcion, precio, stock, categoria_id, imagen) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('sssdisi', $titulo, $autor, $descripcion, $precio, $stock, $cat_id, $imagen);
            }
            if ($stmt->execute()) {
                header('Location: /librerias_jok/admin/libros.php?msg=saved');
                exit;
            } else {
                $error = 'Error al guardar en base de datos.';
            }
            $stmt->close();
        }
    }
}

include __DIR__ . '/header_admin.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="d-flex align-items-center gap-3 mb-4">
      <a href="/librerias_jok/admin/libros.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h4 class="mb-0" style="font-family:'Playfair Display',serif;">
        <?= $editId ? 'Editar: ' . htmlspecialchars($libro['titulo']) : 'Nuevo libro' ?>
      </h4>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-3 shadow-sm p-4">
      <form method="POST" enctype="multipart/form-data" novalidate>

        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Título <span class="text-danger">*</span></label>
            <input type="text" name="titulo" class="form-control"
                   value="<?= htmlspecialchars($libro['titulo'] ?? $_POST['titulo'] ?? '') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Categoría <span class="text-danger">*</span></label>
            <select name="categoria_id" class="form-select" required>
              <option value="">-- Seleccionar --</option>
              <?php foreach ($cats as $c): ?>
              <option value="<?= $c['id'] ?>"
                <?= (($libro['categoria_id'] ?? $_POST['categoria_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Autor <span class="text-danger">*</span></label>
            <input type="text" name="autor" class="form-control"
                   value="<?= htmlspecialchars($libro['autor'] ?? $_POST['autor'] ?? '') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Precio (MXN) <span class="text-danger">*</span></label>
            <input type="number" name="precio" class="form-control" min="1" step="0.01"
                   value="<?= htmlspecialchars($libro['precio'] ?? $_POST['precio'] ?? '') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stock <span class="text-danger">*</span></label>
            <input type="number" name="stock" class="form-control" min="0"
                   value="<?= htmlspecialchars($libro['stock'] ?? $_POST['stock'] ?? '0') ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4"
                      placeholder="Breve sinopsis del libro..."><?= htmlspecialchars($libro['descripcion'] ?? $_POST['descripcion'] ?? '') ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label">Imagen de portada</label>
            <input type="file" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
            <div class="form-text">JPG, PNG, WebP — máx 3MB</div>
          </div>
          <?php if (!empty($libro['imagen'])): ?>
          <div class="col-md-6 d-flex align-items-end">
            <div>
              <div class="form-text mb-1">Imagen actual:</div>
              <img src="/librerias_jok/uploads/<?= htmlspecialchars($libro['imagen']) ?>"
                   style="height:80px;border-radius:6px;border:1px solid #ddd;" alt="">
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="d-flex gap-3 mt-4">
          <button type="submit" class="btn btn-gold px-4">
            <i class="bi bi-check-lg me-2"></i><?= $editId ? 'Guardar cambios' : 'Crear libro' ?>
          </button>
          <a href="/librerias_jok/admin/libros.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer_admin.php'; ?>
