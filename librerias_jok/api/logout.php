<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /librerias_jok/pages/login.php');
exit;
