<?php
session_start();

// Destruye la sesión
session_destroy();

// Redirige al login
header("Location: ../view/login.php");
exit();
