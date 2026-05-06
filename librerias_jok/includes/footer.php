</main>

<footer class="jok-footer mt-auto">
  <div class="container py-4 text-center">
    <div class="footer-logo mb-2">LIBRERIAS <span>JOK</span></div>
    <p class="mb-1 text-muted small">Donde cada libro es una aventura</p>
    <p class="mb-0 text-muted small">&copy; <?= date('Y') ?> LIBRERIAS JOK. Todos los derechos reservados.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/librerias_jok/public/js/utils.js"></script>
<script src="/librerias_jok/public/js/carrito.js"></script>
<?php if (isset($extraJS)) echo $extraJS; ?>
</body>
</html>
