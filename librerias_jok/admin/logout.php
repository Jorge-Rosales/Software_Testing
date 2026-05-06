<?php
session_start();
session_destroy();
header('Location: /librerias_jok/admin/login.php');
exit;
