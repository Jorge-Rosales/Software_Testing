<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: /librerias_jok/pages/catalogo.php');
} else {
    header('Location: /librerias_jok/pages/login.php');
}
exit;
